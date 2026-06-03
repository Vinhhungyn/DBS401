<?php
function read_log($path, $lines = 200) {
    $out = shell_exec("tail -n {$lines} {$path} 2>/dev/null");
    if (!$out) return [];
    $arr = preg_split('/\r?\n/', $out);
    return array_values(array_filter($arr, function($l) {
        return trim($l) !== '';
    }));
}

function detect_sqli($line) {
    $patterns = [
        "/'\s*OR\s*'?1'?\s*=\s*'?1/i",
        '/UNION\s+(ALL\s+)?SELECT/i',
        '/DROP\s+TABLE/i',
        '/LOAD_FILE/i',
        '/INTO\s+OUTFILE/i',
        '/OR\s+1\s*=\s*1/i',
    ];
    foreach ($patterns as $p) {
        if (preg_match($p, $line)) return true;
    }
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

$mysql_lines    = read_log('/var/log/mysql/general.log', 300);


$proxysql_lines = [];
$blocked_count  = 0;
$px = new mysqli('proxysql', 'radmin', 'radmin123', null, 6032);
if (!$px->connect_error) {
    $res = $px->query("SELECT s.rule_id, s.hits, r.match_pattern, r.error_msg FROM stats_mysql_query_rules s JOIN mysql_query_rules r ON s.rule_id=r.rule_id WHERE s.hits > 0 ORDER BY s.rule_id");
    while ($row = $res->fetch_assoc()) {
        $blocked_count += $row['hits'];
        $proxysql_lines[] = "Rule {$row['rule_id']} | pattern: {$row['match_pattern']} | {$row['error_msg']} | hits: {$row['hits']}";
    }
    $px->close();
}

$total      = count($mysql_lines);
$sqli_count = 0;
$priv_count = 0;
foreach ($mysql_lines as $l) {
    if (detect_sqli($l)) $sqli_count++;
    if (preg_match('/GRANT|REVOKE|CREATE USER/i', $l)) $priv_count++;
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8">
<title>DB Security Monitor</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { background: #0d1117; color: #c9d1d9; font-family: 'Courier New', monospace; font-size: 13px; }
.topbar { background: #161b22; border-bottom: 1px solid #30363d; padding: 14px 24px; display: flex; align-items: center; justify-content: space-between; }
.topbar .brand { color: #58a6ff; font-size: 16px; font-weight: bold; }
.topbar .time  { color: #8b949e; font-size: 12px; }
.stats { display: grid; grid-template-columns: repeat(4,1fr); gap: 12px; padding: 16px 24px; }
.stat-box { background: #161b22; border: 1px solid #30363d; border-radius: 6px; padding: 16px; text-align: center; }
.stat-box .num   { font-size: 28px; font-weight: bold; margin-bottom: 4px; }
.stat-box .label { font-size: 12px; color: #8b949e; }
.c-blue   { color: #58a6ff; }
.c-red    { color: #f85149; }
.c-yellow { color: #d29922; }
.c-green  { color: #3fb950; }
.panels { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; padding: 0 24px 24px; }
.panel { background: #161b22; border: 1px solid #30363d; border-radius: 6px; overflow: hidden; }
.panel-header { padding: 10px 16px; border-bottom: 1px solid #30363d; display: flex; align-items: center; justify-content: space-between; }
.panel-title { color: #e6edf3; font-weight: bold; font-size: 14px; }
.dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 6px; animation: blink 1s infinite; }
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.3} }
.log-body { height: 500px; overflow-y: auto; padding: 8px; }
.log-line { padding: 3px 8px; border-radius: 3px; margin-bottom: 2px; word-break: break-all; line-height: 1.6; }
.log-sqli   { background: rgba(248,81,73,0.15); color: #f85149; border-left: 3px solid #f85149; }
.log-priv   { background: rgba(210,153,34,0.15); color: #d29922; border-left: 3px solid #d29922; }
.log-danger { background: rgba(255,166,87,0.10); color: #ffa657; border-left: 3px solid #ffa657; }
.log-select { color: #79c0ff; }
.log-write  { color: #d2a8ff; }
.log-conn   { color: #6e7681; }
.log-normal { color: #8b949e; }
.badge { display: inline-block; padding: 1px 6px; border-radius: 99px; font-size: 10px; font-weight: bold; margin-right: 4px; }
.badge-sqli  { background: #f85149; color: white; }
.badge-priv  { background: #d29922; color: black; }
.badge-block { background: #3fb950; color: black; }
.legend { padding: 8px 24px 12px; display: flex; gap: 16px; flex-wrap: wrap; }
.legend-item { display: flex; align-items: center; gap: 6px; font-size: 12px; color: #8b949e; }
.ld { width: 10px; height: 10px; border-radius: 2px; }
</style>
</head>
<body>

<div class="topbar">
  <span class="brand">&#128737; Database Security Monitor</span>
  <span class="time">&#8635; Auto refresh 5s &nbsp;|&nbsp; <?= date('H:i:s') ?></span>
</div>

<div class="stats">
  <div class="stat-box"><div class="num c-blue"><?= $total ?></div><div class="label">Tổng query MySQL</div></div>
  <div class="stat-box"><div class="num c-red"><?= $sqli_count ?></div><div class="label">&#9888; SQLi phát hiện</div></div>
  <div class="stat-box"><div class="num c-yellow"><?= $priv_count ?></div><div class="label">&#128081; Thao tác phân quyền</div></div>
  <div class="stat-box"><div class="num c-green"><?= $blocked_count ?></div><div class="label">&#128274; ProxySQL blocked</div></div>
</div>

<div class="legend">
  <div class="legend-item"><div class="ld" style="background:#f85149"></div>SQL Injection</div>
  <div class="legend-item"><div class="ld" style="background:#d29922"></div>Privilege Escalation</div>
  <div class="legend-item"><div class="ld" style="background:#ffa657"></div>Lệnh nguy hiểm</div>
  <div class="legend-item"><div class="ld" style="background:#79c0ff"></div>SELECT</div>
  <div class="legend-item"><div class="ld" style="background:#d2a8ff"></div>INSERT/UPDATE</div>
  <div class="legend-item"><div class="ld" style="background:#6e7681"></div>Connect/Quit</div>
</div>

<div class="panels">

  <!-- MySQL Log -->
  <div class="panel">
    <div class="panel-header">
      <span class="panel-title"><span class="dot" style="background:#3fb950"></span>MySQL General Log</span>
      <span style="color:#8b949e;font-size:11px;"><?= $total ?> dòng</span>
    </div>
    <div class="log-body">
      <?php if ($total === 0): ?>
        <div class="log-line log-normal" style="padding:20px;text-align:center;">Chưa có log...</div>
      <?php else: ?>
        <?php foreach (array_reverse($mysql_lines) as $line):
          $cls   = classify($line);
          $badge = '';
          if ($cls === 'sqli') $badge = '<span class="badge badge-sqli">SQLi</span>';
          elseif ($cls === 'priv') $badge = '<span class="badge badge-priv">PRIV</span>';
        ?>
        <div class="log-line log-<?= $cls ?>"><?= $badge ?><?= htmlspecialchars($line) ?></div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- ProxySQL Log -->
  <div class="panel">
    <div class="panel-header">
      <span class="panel-title"><span class="dot" style="background:#58a6ff"></span>ProxySQL — Query bị chặn</span>
      <span style="color:#8b949e;font-size:11px;"><?= $blocked_count ?> blocked</span>
    </div>
    <div class="log-body">
      <?php if (empty($proxysql_lines)): ?>
        <div class="log-line c-green" style="padding:20px;text-align:center;">&#10003; Chưa có query bị chặn</div>
      <?php else: ?>
        <?php foreach (array_reverse($proxysql_lines) as $line):
          $blocked = stripos($line, 'Blocked') !== false || stripos($line, 'detected') !== false;
        ?>
        <div class="log-line <?= $blocked ? 'log-sqli' : 'log-normal' ?>">
          <?php if ($blocked): ?><span class="badge badge-block">BLOCKED</span><?php endif; ?>
          <?= htmlspecialchars($line) ?>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

</div>
<script>
// Auto refresh chi phan log, khong reload ca trang
setTimeout(function() {
    var scrollPos = document.querySelector('.log-body') 
                  ? document.querySelector('.log-body').scrollTop 
                  : 0;
    location.reload();
}, 5000);

// Giu nguyen scroll position
window.onbeforeunload = function() {
    sessionStorage.setItem('scrollPos', 
        document.querySelectorAll('.log-body')[0]?.scrollTop || 0);
};

window.onload = function() {
    var pos = sessionStorage.getItem('scrollPos');
    if (pos) {
        var panels = document.querySelectorAll('.log-body');
        if (panels[0]) panels[0].scrollTop = parseInt(pos);
    }
};
</script>
</body>
</html>