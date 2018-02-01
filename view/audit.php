<h1>知乎抓取内容的审核</h1>

<div class="ml-1" style="margin:10px;">
  <?php foreach ($cate_list as $cate => $cate_name): ?>
    <a href="?state=<?= $cate ?>" class="ml-1 btn <?= $cate == $cur_cate ? 'btn-primary' : 'btn-light' ?>">
      <?= htmlspecialchars($cate_name) ?> <span class="badge badge-light"><?= zhihu_fetch::countByCate($cate) ?></span>
    </a>
  <?php endforeach; ?>
</div>

<div class="alert alert-warning text-center pagination-centered" id="undo_box" style="display:none;">
  <span id="undo_box_msg"></span>
  <a href="javascript:void(0);" onclick="undo()" class="btn btn-warning btn-sm">撤销</a>
</div>

<table class="table">
  <thead>
    <tr>
      <th scope="col">#</th>
      <th scope="col">回答者</th>
      <th scope="col">回答数量</th>
      <th scope="col">备注</th>
      <th scope="col">操作(更改状态)</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($entry_list as $key => $entry):
      $author = zhihu_user::getByName($entry['username']);
      $author_show_name = ($author['showname']);
      ?>
      <tr>
        <th scope="row"><?= $key+1 ?></th>
        <td>
          <a href="//zhihu.com/people/<?= htmlspecialchars($entry['username']) ?>" target="_blank">
            <?= htmlspecialchars($author_show_name) ?>
          </a>
        </td>
        <td><?= htmlspecialchars($entry['c']) ?></td>
        <td><?= htmlspecialchars($author['remark']) ?></td>
        <td>
          <?php foreach ($cate_list as $cate => $cate_name): if ($cate != $author['state']) { ?>
            <button type="button" name="button" class="btn btn-outline-primary btn-sm"
              onclick="change_to_state(this, <?= $cate ?>)"
              data-id="<?= $author['username'] ?>"
              data-q="<?= htmlentities(mb_strimwidth($q['title'], 0, 30, '...')) ?>"
              data-author="<?= htmlentities($author_show_name) ?>"
              >
              <?= htmlspecialchars($cate_name) ?>
            </button>
          <?php } endforeach; ?>
          <?php if ($author['state'] == zhihu_fetch::STATE_NOT_PROC): ?>
            <a href="javascript:void(0)" onclick="send_msg('<?= $entry['username'] ?>')">给作者发请求转载私信</a>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<div class="">
  共<?= $total ?>条，显示<?= count($entry_list) ?>条
</div>

<script src="/qa-content/js.cookie.js" charset="utf-8"></script>
<script type="text/javascript">
  function ajaxDo(method, url, data, func) {
    var request = new XMLHttpRequest();
    request.open(method, url, true);
    request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
    request.onload = func;
    if (data) {
      request.send(data);
    } else {
      request.send();
    }
  }
  var MAP_STATE = <?= json_encode($cate_list) ?>;
  function change_to_state(elem, new_state) {
    var yes = confirm("确实要把 "+elem.getAttribute('data-author')+" 对 "+elem.getAttribute('data-q')+" 的回答改为 "+MAP_STATE[new_state]+" 状态吗？");
    if (yes) {
      var data = [
        "change_to_state="+encodeURIComponent(new_state),
        "id="+encodeURIComponent(elem.getAttribute('data-id')),
      ];
      ajaxDo('POST', '?ajax', data.join("&"), function() {
        if (this.status >= 200 && this.status < 400) {
          // Success!
          var resp = this.responseText;
          Cookies.set('undo', elem.getAttribute('data-id'));
          Cookies.set('undo_text', elem.getAttribute('data-author')+" 对 "+elem.getAttribute('data-q')+" 的回答");
          Cookies.set('old_state', <?= $cur_cate ?>);
          alert(resp);
          location.href = "?state="+new_state;
        } else {
          // We reached our target server, but it returned an error
          alert("error");
        }
      });
    }
  }
  var undo_id = Cookies.get('undo');
  if (undo_id && undo_id != "0") {
    show_undo();
  }
  function show_undo() {
    var id = Cookies.get('undo');
    var undo_text = Cookies.get('undo_text');
    var old_state = Cookies.get('old_state');
    document.getElementById('undo_box_msg').textContent = "您刚刚更改了 "+undo_text;
    var undo_box = document.getElementById('undo_box');
    undo_box.style.display = '';
    setTimeout(function() {
      undo_box.style.display = 'none';
      Cookies.set('undo', "");
    }, 5000);
  }
  function undo() {
    var id = Cookies.get('undo');
    Cookies.set('undo', "");
    var undo_text = Cookies.get('undo_text');
    var old_state = Cookies.get('old_state');
    var data = [
      "change_to_state="+ encodeURIComponent(old_state),
      "id="+ encodeURIComponent(id),
    ];
    ajaxDo('POST', '?ajax', data.join("&"), function() {
      if (this.status >= 200 && this.status < 400) {
        // Success!
        var resp = this.responseText;
        alert(resp);
        location.href = "?state="+old_state;
      } else {
        // We reached our target server, but it returned an error
        alert("error");
      }
    });
  }
</script>
