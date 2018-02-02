<h1>欢迎 <?= htmlspecialchars($author['showname']) ?> 前来注册 多知乎</h1>

<div class="ml-1" style="margin:10px;">
  <?php foreach ($cate_list as $cate => $cate_name): ?>
    <a href="?state=<?= $cate ?>" class="ml-1 btn <?= $cate == $cur_cate ? 'btn-primary' : 'btn-light' ?>">
      <?= htmlspecialchars($cate_name) ?> <span class="badge badge-light"><?= zhihu_user::countByCate($cate) ?></span>
    </a>
  <?php endforeach; ?>
</div>

<div class="alert alert-warning text-center pagination-centered" id="undo_box" style="display:none;">
  <span id="undo_box_msg"></span>
  <a href="javascript:void(0);" onclick="undo()" class="btn btn-warning btn-sm">撤销</a>
</div>
