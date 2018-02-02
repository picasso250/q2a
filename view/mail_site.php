<?= ($author['showname']) ?>，你好。
你是高手，是大V。我最近新做了个问答网站，请你来入驻。
入驻链接：http://duozhihu/zhihu/user-guide.php?u=<?= ($author['salt']) ?>
此网站和知乎相比优势在于：使用门罗币做为代币防止spam和水化。
如果不愿，能否允许我转载你在以下问题下的答案：
<?php foreach ($answer_list as $answer): $question = zhihu_fetch::getQuestionByZhihuId($answer['qid']) ?>
  <?php strip_tags($question['title']) ?>
  链接: https://www.zhihu.com/question/<?= $answer['qid'] ?>/answer/<?= $answer['aid'] ?>/
<?php endforeach ?>