<h1>Armory — Top 10 Characters</h1>
<table>
  <thead><tr><th>Name</th><th>Level</th><th>GUID</th></tr></thead>
  <tbody>
  <?php foreach ($rows ?? [] as $r): ?>
    <tr>
      <td><?= htmlspecialchars($r['name']) ?></td>
      <td><?= (int)$r['level'] ?></td>
      <td><?= (int)$r['guid'] ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
