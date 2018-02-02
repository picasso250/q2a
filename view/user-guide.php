<h1>欢迎 <?= htmlspecialchars($author['showname']) ?> 前来注册 多知乎</h1>

<div class="">
  您是大V。我们看上的回答有:
  <ul>
    <?php foreach ($answer_list as $answer): $question = zhihu_fetch::getQuestionByZhihuId($answer['qid']) ?>
      <li>
        <a target="_blank" href="https://www.zhihu.com/question/<?= $answer['qid'] ?>/answer/<?= $answer['aid'] ?>/">
          <?= htmlspecialchars(strip_tags($question['title'])) ?>
          <br>
          <?= htmlspecialchars(mb_strimwidth(strip_tags($answer['detail']), 0, 90, "...")) ?>
        </a>
      </li>
    <?php endforeach ?>
  </ul>
  如您同意注册，则同意将以上回答转载到此网站。
  <a href="/?qa=register&<?= http_build_query(['to' => $back_url]) ?>">去注册</a>
</div>
