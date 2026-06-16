<?php
// ============================================================
// log-dashboard/index.php — Real-time Security Monitor
// ============================================================
ob_start();
set_time_limit(10);
ini_set('default_socket_timeout', 3);
mysqli_report(MYSQLI_REPORT_OFF);

function read_log($path, $lines = 300) {
    $out = shell_exec("tail -n {$lines} {$path} 2>/dev/null");
    if (!$out) return [];
    return array_values(array_filter(preg_split('/\r?\n/', $out), fn($l) => trim($l) !== ''));
}

function detect_sqli($line) {
    $patterns = [
        "/'\s*OR\s*'?1'?\s*=\s*'?1/i",
        '/UNION\s+(ALL\s+)?SELECT/i',
        '/DROP\s+TABLE/i', '/LOAD_FILE/i',
        '/INTO\s+OUTFILE/i', '/OR\s+1\s*=\s*1/i',
    ];
    foreach ($patterns as $p) if (preg_match($p, $line)) return true;
    return false;
}

function classify($line) {
    if (detect_sqli($line))                               return 'sqli';
    if (preg_match('/GRANT|REVOKE|CREATE USER/i', $line)) return 'priv';
    if (preg_match('/DROP|TRUNCATE/i', $line))            return 'danger';
    if (preg_match('/SELECT/i', $line))                   return 'select';
    if (preg_match('/INSERT|UPDATE|DELETE/i', $line))     return 'write';
    if (preg_match('/Connect|Quit/i', $line))             return 'conn';
    return 'normal';
}

// Parse Apache access log → bảng sự kiện
function parse_apache_log($lines) {
    $events = [];
    foreach ($lines as $line) {
        // Format: IP - - [date] "METHOD URI HTTP" CODE size "ref" "ua"
        if (preg_match('/^(\S+)\s+\S+\s+\S+\s+\[([^\]]+)\]\s+"(\S+)\s+(\S+)\s+\S+"\s+(\d+)/', $line, $m)) {
            $events[] = [
                'time'   => $m[2],
                'ip'     => $m[1],
                'method' => $m[3],
                'uri'    => $m[4],
                'code'   => (int)$m[5],
                'msg'    => '',
            ];
        }
    }
    return $events;
}

// Parse ModSecurity error log → lấy thông tin rule bị trigger
function parse_modsec_log($lines) {
    $blocks = [];
    foreach ($lines as $line) {
        if (strpos($line, 'ModSecurity') === false) continue;
        $time = $uri = $msg = $id = '';
        if (preg_match('/\[(\w+ \w+ \d+ [\d:]+\.\d+ \d+)\]/', $line, $m)) $time = $m[1];
        if (preg_match('/\[uri "([^"]+)"\]/', $line, $m)) $uri = $m[1];
        if (preg_match('/\[msg "([^"]+)"\]/', $line, $m)) $msg = $m[1];
        if (preg_match('/\[id "(\d+)"\]/', $line, $m)) $id = $m[1];
        $is_block = strpos($line, 'Access denied') !== false;
        if ($uri || $msg) {
            $blocks[] = ['time'=>$time,'uri'=>$uri,'msg'=>$msg,'id'=>$id,'blocked'=>$is_block];
        }
    }
    return $blocks;
}

$mysql_lines  = read_log('/var/log/mysql/general.log', 300);
$modsec_lines = read_log('/var/log/apache2/error_real.log', 300);

// Đọc cả 2 webapp
$apache_protected = read_log('/var/log/apache2/access_real.log', 200);       // 5001
$apache_vuln      = read_log('/var/log/apache2_vuln/access_vuln.log', 200);  // 5000

// Merge + parse
$apache_events = parse_apache_log(array_merge($apache_protected, $apache_vuln));

// Sort mới → cũ theo timestamp
usort($apache_events, function($a, $b) {
    return strtotime($b['time']) - strtotime($a['time']);
});

$modsec_events = parse_modsec_log($modsec_lines);

// ProxySQL stats
$proxysql_lines = [];
$blocked_count  = 0;
$px = @new mysqli('proxysql', 'radmin', 'radmin123', null, 6032);

if (!$px->connect_error) {
    $res = $px->query("SELECT s.rule_id, s.hits, r.match_pattern, r.error_msg FROM stats_mysql_query_rules s JOIN mysql_query_rules r ON s.rule_id=r.rule_id WHERE s.hits > 0 ORDER BY s.rule_id");
    while ($row = $res->fetch_assoc()) {
        $blocked_count += $row['hits'];
        $proxysql_lines[] = $row;
    }
    $px->close();
}

$total      = count($mysql_lines);
$sqli_count = 0;
foreach ($mysql_lines as $l) if (detect_sqli($l)) $sqli_count++;
$modsec_block_count = count(array_filter($modsec_events, fn($e) => $e['blocked']));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8">
<title>DB Security Monitor</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{background:#0d1117;color:#c9d1d9;font-family:'Courier New',monospace;font-size:13px;}
.topbar{background:#161b22;border-bottom:1px solid #30363d;padding:14px 24px;display:flex;align-items:center;justify-content:space-between;}
.brand{color:#58a6ff;font-size:16px;font-weight:bold;}
.time{color:#8b949e;font-size:12px;}
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;padding:16px 24px;}
.stat-box{background:#161b22;border:1px solid #30363d;border-radius:6px;padding:16px;text-align:center;}
.num{font-size:28px;font-weight:bold;margin-bottom:4px;}
.lbl{font-size:12px;color:#8b949e;}
.c-blue{color:#58a6ff;}.c-red{color:#f85149;}.c-yellow{color:#d29922;}.c-green{color:#3fb950;}
.panels{display:flex;flex-direction:column;gap:12px;padding:0 24px 24px;}
.panel{background:#161b22;border:1px solid #30363d;border-radius:6px;overflow:hidden;}
.panel-header{padding:10px 16px;border-bottom:1px solid #30363d;display:flex;align-items:center;justify-content:space-between;}
.panel-title{color:#e6edf3;font-weight:bold;font-size:14px;}
.dot{width:8px;height:8px;border-radius:50%;display:inline-block;margin-right:6px;animation:blink 1s infinite;}
@keyframes blink{0%,100%{opacity:1}50%{opacity:0.3}}
.log-body{max-height:320px;overflow-y:auto;padding:8px;}
.log-line{padding:3px 8px;border-radius:3px;margin-bottom:2px;word-break:break-all;line-height:1.6;}
.log-sqli{background:rgba(248,81,73,0.15);color:#f85149;border-left:3px solid #f85149;}
.log-priv{background:rgba(210,153,34,0.15);color:#d29922;border-left:3px solid #d29922;}
.log-danger{background:rgba(255,166,87,0.10);color:#ffa657;border-left:3px solid #ffa657;}
.log-select{color:#79c0ff;}.log-write{color:#d2a8ff;}.log-conn{color:#6e7681;}.log-normal{color:#8b949e;}
.badge{display:inline-block;padding:1px 6px;border-radius:99px;font-size:10px;font-weight:bold;margin-right:4px;}
.badge-sqli{background:#f85149;color:white;}.badge-priv{background:#d29922;color:black;}
.badge-block{background:#f85149;color:white;}.badge-warn{background:#d29922;color:black;}
/* TABLE */
.event-table{width:100%;border-collapse:collapse;}
.event-table th{background:#21262d;color:#8b949e;padding:8px 12px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:0.5px;border-bottom:1px solid #30363d;}
.event-table td{padding:7px 12px;border-bottom:1px solid #21262d;font-size:12px;vertical-align:middle;}
.event-table tr:hover td{background:#1c2128;}
.code-200{color:#3fb950;font-weight:bold;}
.code-403{color:#f85149;font-weight:bold;}
.code-404{color:#8b949e;}
.code-301,.code-302{color:#d29922;}
.method-get{color:#58a6ff;}.method-post{color:#3fb950;}.method-delete{color:#f85149;}
.row-blocked{background:rgba(248,81,73,0.08)!important;}
</style>
</head>
<body>

<div class="topbar">
  <span class="brand">🛡 Database Security Monitor</span>
  <span class="time">↻ Auto refresh 5s &nbsp;|&nbsp; <?= date('H:i:s') ?></span>
</div>

<div class="stats">
  <div class="stat-box"><div class="num c-blue"><?= $total ?></div><div class="lbl">Tổng query MySQL</div></div>
  <div class="stat-box"><div class="num c-red"><?= $sqli_count ?></div><div class="lbl">⚠ SQLi phát hiện</div></div>
  <div class="stat-box"><div class="num c-yellow"><?= $modsec_block_count ?></div><div class="lbl">🛡 ModSecurity blocked</div></div>
  <div class="stat-box"><div class="num c-green"><?= $blocked_count ?></div><div class="lbl">🔒 ProxySQL blocked</div></div>
</div>

<div class="panels">

  <!-- BANG SU KIEN WAF (ModSecurity + Apache) -->
  <div class="panel">
    <div class="panel-header">
      <span class="panel-title"><span class="dot" style="background:#f85149"></span>15 Sự kiện gần nhất — WAF (ModSecurity)</span>
      <span style="color:#8b949e;font-size:11px;"><?= $modsec_block_count ?> blocked</span>
    </div>
    <div style="overflow-x:auto;">
      <table class="event-table">
        <tr>
          <th>Thời gian</th>
          <th>IP</th>
          <th>Code</th>
          <th>Method</th>
          <th>URI</th>
          <th>Rule Message</th>
        </tr>
        <?php
        $merged = [];
        foreach ($apache_events as $ev) {
            $key = $ev['uri'];
            $msg = '';
            foreach ($modsec_events as $me) {
                if ($me['uri'] === $key && $me['blocked']) {
                    $msg = $me['msg'] ?: 'ModSecurity blocked';
                    break;
                }
            }
            $ev['msg'] = $msg;
            $merged[] = $ev;
            if (count($merged) >= 15) break;
        }
        foreach ($merged as $ev):
            $code_cls = 'code-' . $ev['code'];
            $method_cls = 'method-' . strtolower($ev['method']);
            $row_cls = $ev['code'] == 403 ? 'row-blocked' : '';
            $badge = $ev['code'] == 403 ? '<span class="badge badge-block">BLOCKED</span>' : '';
            $msg = htmlspecialchars($ev['msg']);
        ?>
        <tr class="<?= $row_cls ?>">
          <td style="color:#8b949e;white-space:nowrap;"><?= htmlspecialchars($ev['time']) ?></td>
          <td style="color:#79c0ff;"><?= htmlspecialchars($ev['ip']) ?></td>
          <td class="<?= $code_cls ?>"><?= $badge ?><?= $ev['code'] ?></td>
          <td class="<?= $method_cls ?>"><?= htmlspecialchars($ev['method']) ?></td>
          <td style="color:#e6edf3;"><?= htmlspecialchars($ev['uri']) ?></td>
          <td style="color:<?= $ev['code']==403?'#f85149':'#8b949e' ?>;"><?= $msg ?: '-' ?></td>
        </tr>
        <?php endforeach; ?>
      </table>
    </div>
  </div>

  <!-- MySQL General Log -->
  <div class="panel">
    <div class="panel-header">
      <span class="panel-title"><span class="dot" style="background:#3fb950"></span>MySQL General Log</span>
      <span style="color:#8b949e;font-size:11px;"><?= $total ?> dòng</span>
    </div>
    <div class="log-body">
      <?php foreach (array_reverse($mysql_lines) as $line):
        $cls = classify($line);
        $badge = '';
        if ($cls === 'sqli') $badge = '<span class="badge badge-sqli">SQLi</span>';
        elseif ($cls === 'priv') $badge = '<span class="badge badge-priv">PRIV</span>';
      ?>
      <div class="log-line log-<?= $cls ?>"><?= $badge ?><?= htmlspecialchars($line) ?></div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- ProxySQL blocked rules -->
  <div class="panel">
    <div class="panel-header">
      <span class="panel-title"><span class="dot" style="background:#58a6ff"></span>ProxySQL — Query bị chặn</span>
      <span style="color:#8b949e;font-size:11px;"><?= $blocked_count ?> hits</span>
    </div>
    <div style="overflow-x:auto;">
      <table class="event-table">
        <tr><th>Rule ID</th><th>Pattern</th><th>Message</th><th>Hits</th></tr>
        <?php if (empty($proxysql_lines)): ?>
        <tr><td colspan="4" style="text-align:center;color:#3fb950;padding:20px;">✓ Chưa có query bị chặn</td></tr>
        <?php else: ?>
        <?php foreach ($proxysql_lines as $row): ?>
        <tr class="row-blocked">
          <td class="c-red"><?= $row['rule_id'] ?></td>
          <td style="color:#8b949e;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($row['match_pattern']) ?></td>
          <td style="color:#f85149;"><?= htmlspecialchars($row['error_msg']) ?></td>
          <td class="c-yellow"><?= $row['hits'] ?></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </table>
    </div>
  </div>

</div>

<script>
setTimeout(function(){ location.reload(); }, 5000);
</script>
</body>
</html>