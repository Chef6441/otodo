<?php
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$db = get_db();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $db->prepare('SELECT id, description, due_date, details, done, priority FROM tasks WHERE id = :id AND user_id = :uid');
$stmt->execute([':id' => $id, ':uid' => $_SESSION['user_id']]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$task) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $description = ucwords(strtolower(trim($_POST['description'] ?? '')));
    $due_date = trim($_POST['due_date'] ?? '');
    $details = trim($_POST['details'] ?? '');
    $priority = (int)($_POST['priority'] ?? 0);
    if ($priority < 0 || $priority > 3) {
        $priority = 0;
    }
    $done = isset($_POST['done']) ? 1 : 0;
    $stmt = $db->prepare('UPDATE tasks SET description = :description, due_date = :due_date, details = :details, priority = :priority, done = :done WHERE id = :id AND user_id = :uid');
    $stmt->execute([
        ':description' => $description,
        ':due_date' => $due_date !== '' ? $due_date : null,
        ':details' => $details !== '' ? $details : null,
        ':priority' => $priority,
        ':done' => $done,
        ':id' => $id,
        ':uid' => $_SESSION['user_id'],
    ]);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'ok']);
    exit();
}

$priority_classes = [
    0 => 'bg-secondary-subtle text-secondary',
    1 => 'bg-success-subtle text-success',
    2 => 'bg-warning-subtle text-warning',
    3 => 'bg-danger-subtle text-danger'
];
$p = (int)($task['priority'] ?? 0);
if ($p < 0 || $p > 3) { $p = 0; }
$special_prefixes = $_SESSION['special_prefixes'] ?? "T \nN \nX \nC \nM \n# \n## \n### ";
if (is_array($special_prefixes)) {
    $special_prefixes = implode("\n", $special_prefixes);
}
$prefixArray = array_values(array_filter(explode("\n", $special_prefixes), 'strlen'));
usort($prefixArray, function($a, $b) { return strlen($b) - strlen($a); });
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        #prioritySelect option.bg-secondary-subtle:hover,
        #prioritySelect option.bg-secondary-subtle:focus,
        #prioritySelect option.bg-secondary-subtle:active {
            background-color: var(--bs-secondary-bg-subtle) !important;
            color: var(--bs-secondary-text-emphasis) !important;
        }
        #prioritySelect option.bg-success-subtle:hover,
        #prioritySelect option.bg-success-subtle:focus,
        #prioritySelect option.bg-success-subtle:active {
            background-color: var(--bs-success-bg-subtle) !important;
            color: var(--bs-success-text-emphasis) !important;
        }
        #prioritySelect option.bg-warning-subtle:hover,
        #prioritySelect option.bg-warning-subtle:focus,
        #prioritySelect option.bg-warning-subtle:active {
            background-color: var(--bs-warning-bg-subtle) !important;
            color: var(--bs-warning-text-emphasis) !important;
        }
        #prioritySelect option.bg-danger-subtle:hover,
        #prioritySelect option.bg-danger-subtle:focus,
        #prioritySelect option.bg-danger-subtle:active {
            background-color: var(--bs-danger-bg-subtle) !important;
            color: var(--bs-danger-text-emphasis) !important;

        }
        #prioritySelect:hover,
        #prioritySelect:focus {
            background-color: inherit !important;
            color: inherit !important;
        }
        @media (min-width: 992px) {
            #detailsInput {
                min-height: 30rem;
            }
        }
    </style>
    <title>Task Details</title>
</head>
<body class="bg-light">
<nav class="navbar navbar-light bg-white mb-4">

    <div class="container d-flex justify-content-between align-items-center">
        <a href="index.php" class="navbar-brand">Otodo</a>
        <div class="d-flex align-items-center gap-2">
            <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm" type="button" id="taskMenu" data-bs-toggle="dropdown" aria-expanded="false">&#x2026;</button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="taskMenu">
                    <li><a class="dropdown-item text-danger" href="delete_task.php?id=<?=$task['id']?>">Delete</a></li>
                </ul>
            </div>
        </div>

    </div>
</nav>

<div class="offcanvas offcanvas-start" tabindex="-1" id="menu" aria-labelledby="menuLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="menuLabel">Menu</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <p class="mb-4">Hello, <?=htmlspecialchars($_SESSION['username'] ?? '')?></p>
        <div class="list-group">
            <a href="index.php" class="list-group-item list-group-item-action">Active Tasks</a>
            <a href="completed.php" class="list-group-item list-group-item-action">Completed Tasks</a>
            <a href="settings.php" class="list-group-item list-group-item-action">Settings</a>
            <a href="logout.php" class="list-group-item list-group-item-action">Logout</a>
        </div>
    </div>
</div>
<div class="container">
    <form method="post">
        <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" name="description" class="form-control" value="<?=htmlspecialchars(ucwords(strtolower($task['description'] ?? '')))?>" required autocapitalize="none">
        </div>
        <div class="mb-3 d-flex align-items-end gap-3">
            <div>
                <label class="form-label">Due Date</label>
                <input type="date" name="due_date" class="form-control w-auto" value="<?=htmlspecialchars($task['due_date'] ?? '')?>">
            </div>
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="done" id="doneCheckbox" <?php if ($task['done']) echo 'checked'; ?>>
                <label class="form-check-label" for="doneCheckbox">Completed</label>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Priority</label>
            <select name="priority" id="prioritySelect" class="form-select <?=$priority_classes[$p]?>">
                <option value="0" class="bg-secondary-subtle text-secondary" <?php if (($task['priority'] ?? 0) == 0) echo 'selected'; ?>>None</option>
                <option value="3" class="bg-danger-subtle text-danger" <?php if (($task['priority'] ?? 2) == 3) echo 'selected'; ?>>High</option>
                <option value="2" class="bg-warning-subtle text-warning" <?php if (($task['priority'] ?? 2) == 2) echo 'selected'; ?>>Medium</option>
                <option value="1" class="bg-success-subtle text-success" <?php if (($task['priority'] ?? 2) == 1) echo 'selected'; ?>>Low</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="details" id="detailsInput" class="form-control" rows="5"><?=htmlspecialchars($task['details'] ?? '')?></textarea>
        </div>
        <a href="index.php" class="btn btn-secondary">Back</a>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function(){
  const specialPrefixes = <?php echo json_encode($prefixArray); ?>;
  const select = document.querySelector('select[name="priority"]');
  const badge = document.getElementById('priorityBadge');
  if (select && badge) {
    const labels = {0: 'None', 1: 'Low', 2: 'Medium', 3: 'High'};
    const classes = {0: 'bg-secondary-subtle text-secondary', 1: 'bg-success-subtle text-success', 2: 'bg-warning-subtle text-warning', 3: 'bg-danger-subtle text-danger'};
    function updateBadge() {
      const val = parseInt(select.value, 10);
      badge.textContent = labels[val] || 'None';
      badge.className = 'badge ' + (classes[val] || classes[0]);
    }
    select.addEventListener('change', updateBadge);
  }

  const form = document.querySelector('form');
  if (!form) return;
  let timer;

  function scheduleSave() {
    if (timer) clearTimeout(timer);
    timer = setTimeout(sendSave, 500);
  }

  function sendSave(immediate = false) {
    const data = new FormData(form);
    if (immediate && navigator.sendBeacon) {
      navigator.sendBeacon(window.location.href, data);
    } else {
      fetch(window.location.href, {method: 'POST', body: data});
    }
  }

  const details = document.getElementById('detailsInput');
  if (details) {
      details.addEventListener('keydown', function(e) {
        if (e.key === 'Tab') {
          e.preventDefault();
          const start = this.selectionStart;
          const end = this.selectionEnd;
          this.value = this.value.slice(0, start) + "\t" + this.value.slice(end);
          this.selectionStart = this.selectionEnd = start + 1;
          scheduleSave();
        } else if (e.key === ' ') {
          const start = this.selectionStart;
          const end = this.selectionEnd;
          if (start === end && start > 0 && this.value[start - 1] === ' ') {
            e.preventDefault();
            this.value = this.value.slice(0, start - 1) + "\t" + this.value.slice(end);
            this.selectionStart = this.selectionEnd = start;
            scheduleSave();
          }
        } else if (e.key === 'Enter') {
          e.preventDefault();
          const start = this.selectionStart;
          const end = this.selectionEnd;
          const value = this.value;
          const lineStart = value.lastIndexOf('\n', start - 1) + 1;
          const lineBreak = value.indexOf('\n', start);
          const lineEnd = lineBreak === -1 ? value.length : lineBreak;
          const line = value.slice(lineStart, lineEnd);
          const leading = line.match(/^[\t ]*/)[0];
          if (/^[\t ]*$/.test(line)) {
            const before = value.slice(0, lineStart);
            const after = lineBreak === -1 ? '' : value.slice(lineBreak + 1);
            this.value = before + "\n" + after;
            const pos = before.length + 1;
            this.selectionStart = this.selectionEnd = pos;
          } else {
            const before = value.slice(0, start);
            const after = value.slice(end);
            this.value = before + "\n" + leading + after;
            const pos = start + 1 + leading.length;
            this.selectionStart = this.selectionEnd = pos;
          }
          scheduleSave();
        }
      });
      details.addEventListener('input', function() {
        const pos = this.selectionStart;
        const lines = this.value.split('\n');
        for (let i = 0; i < lines.length; i++) {
          let line = lines[i];
          const leading = line.match(/^[\t ]*/)[0];
          let rest = line.slice(leading.length);
          let prefix = '';
          for (const p of specialPrefixes) {
            if (rest.startsWith(p)) {
              prefix = p;
              rest = rest.slice(p.length);
              break;
            }
          }
          if (rest.length > 0) {
            rest = rest.charAt(0).toUpperCase() + rest.slice(1);
          }
          lines[i] = leading + prefix + rest;
        }
        const newValue = lines.join('\n');
        if (newValue !== this.value) {
          this.value = newValue;
          this.selectionStart = this.selectionEnd = pos;
        }
        scheduleSave();
      });
    }

  form.addEventListener('input', scheduleSave);
  form.addEventListener('change', scheduleSave);
  form.addEventListener('submit', function(e){ e.preventDefault(); });
  window.addEventListener('beforeunload', function(){
    if (timer) {
      sendSave(true);
    }
  });
})();
</script>
<script src="sw-register.js"></script>
</body>
</html>
