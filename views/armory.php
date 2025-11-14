<?php
use App\WowHelper;
/** @var array $rows */
?>

<h1>Armory — Top 10 Characters</h1>

<table>
  <thead>
    <tr>
      <th>Name</th>
      <th>Level</th>
      <th>Class</th>
      <th>Race</th>
      <th>GUID</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($rows ?? [] as $r): ?>
    <?php
      $classId = (int)$r['class'];
      $raceId  = (int)$r['race'];

      $gender = null;
      if (array_key_exists('gender', $r)) {
          $gender = $r['gender'] !== null ? (int)$r['gender'] : null;
      }

      $className = WowHelper::className($classId);
      $raceName  = WowHelper::raceName($raceId);

      $classIcon = WowHelper::classIcon($classId);
      $raceIcon  = WowHelper::raceIcon($raceId, $gender);
    ?>
    <tr>
      <td><?= htmlspecialchars($r['name']) ?></td>
      <td><?= (int)$r['level'] ?></td>
      <td>
        <img src="<?= htmlspecialchars($classIcon) ?>"
             alt="<?= htmlspecialchars($className) ?>"
             width="18" height="18"
             style="vertical-align:-3px;margin-right:6px">
        <?= htmlspecialchars($className) ?>
      </td>
      <td>
        <img src="<?= htmlspecialchars($raceIcon) ?>"
             alt="<?= htmlspecialchars($raceName) ?>"
             width="18" height="18"
             style="vertical-align:-3px;margin-right:6px">
        <?= htmlspecialchars($raceName) ?>
      </td>
      <td><?= (int)$r['guid'] ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
