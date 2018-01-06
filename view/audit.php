<table class="table">
  <thead>
    <tr>
      <th scope="col">#</th>
      <th scope="col">问题</th>
      <th scope="col">回答</th>
      <th scope="col">回答者</th>
      <th scope="col">备注</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($entry_list as $key => $entry): ?>
      <tr>
        <th scope="row"><?= $key+1 ?></th>
        <td><?= htmlspecialchars(mb_strimwidth(zhihu_fetch::getQ_byId($entry['qid'])['title'], 0, 30, '...')) ?></td>
        <td>
          <a href="//zhihu.com/question/<?= htmlspecialchars($entry['qid']) ?>/answer/<?= htmlspecialchars($entry['aid']) ?>" target="_blank">
            <?= htmlspecialchars(mb_strimwidth(strip_tags($entry['detail']), 0, 60, '...')) ?>
          </a>
        </td>
        <td>
          <a href="//zhihu.com/people/<?= htmlspecialchars($entry['username']) ?>" target="_blank">
            <?= htmlspecialchars(zhihu_user::getByName($entry['username'])['showname']) ?>
          </a>
        </td>
        <td><?= htmlspecialchars($entry['remark']) ?></a></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<div class="">
  共<?= $total ?>条，显示<?= count($entry_list) ?>条
</div>
