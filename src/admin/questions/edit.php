<?php
// dbconnect.phpを読み込む
require_once('../../dbconnect.php');
$dbh = new PDO($dsn, $user, $password);

// 問題IDの取得削除処理は
if (isset($_GET['id'])) {
    $question_id = $_GET['id'];

    // 選択された問題のデータを取得
    $sqlGetQuestion = 'SELECT * FROM questions WHERE id = :id';
    $stmtGetQuestion = $dbh->prepare($sqlGetQuestion);
    $stmtGetQuestion->bindValue(':id', $question_id, PDO::PARAM_INT);
    $stmtGetQuestion->execute();
    $question = $stmtGetQuestion->fetch(PDO::FETCH_ASSOC);

    // 問題に関連する選択肢のデータを取得
    $sqlGetChoices = 'SELECT * FROM choices WHERE question_id = :question_id';
    $stmtGetChoices = $dbh->prepare($sqlGetChoices);
    $stmtGetChoices->bindValue(':question_id', $question_id, PDO::PARAM_INT);
    $stmtGetChoices->execute();
    $choices = $stmtGetChoices->fetchAll(PDO::FETCH_ASSOC);
} else {
    // 問題IDが指定されていない場合はエラー処理などを行う
    echo '問題IDが指定されていません。';
    exit();
}

// CSRFトークンの生成
$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;

// フォームが送信された場合の処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // トランザクションを開始
        $dbh->beginTransaction();

        // 問題の更新
        $sqlUpdateQuestion = 'UPDATE questions SET content = :content WHERE id = :id';
        $stmtUpdateQuestion = $dbh->prepare($sqlUpdateQuestion);
        $stmtUpdateQuestion->bindValue(':content', $_POST['content'], PDO::PARAM_STR);
        $stmtUpdateQuestion->bindValue(':id', $question_id, PDO::PARAM_INT);
        $stmtUpdateQuestion->execute();

        // choicesテーブルの削除
        $sqlDeleteChoices = 'DELETE FROM choices WHERE question_id = :question_id';
        $stmtDeleteChoices = $dbh->prepare($sqlDeleteChoices);
        $stmtDeleteChoices->bindValue(':question_id', $question_id, PDO::PARAM_INT);
        $stmtDeleteChoices->execute();

        // choicesテーブルの挿入
        $sqlInsertChoice = 'INSERT INTO choices (name, valid, question_id) VALUES (:name, :valid, :question_id)';
        $stmtInsertChoice = $dbh->prepare($sqlInsertChoice);

        // 選択肢の数（動的に変わる可能性があるので count 関数は使わない）
        $numChoices = count($_POST['choices']);

        // 選択肢の数だけループ
        for ($i = 0; $i < $numChoices; $i++) {
            $stmtInsertChoice->bindValue(':name', $_POST['choices'][$i], PDO::PARAM_STR);

            // ラジオボタンの値がそのまま valid に入る
            $stmtInsertChoice->bindValue(':valid', isset($_POST['correct_choice']) && $_POST['correct_choice'] == $i ? 1 : 0, PDO::PARAM_INT);

            $stmtInsertChoice->bindValue(':question_id', $question_id, PDO::PARAM_INT);
            $stmtInsertChoice->execute();
        }

        // トランザクションをコミット
        $dbh->commit();

        // 更新が完了したら適切なリダイレクトなどを行う
        // 例: 編集画面にリダイレクト
        header("Location: ../index.php");
        exit();
    } catch (PDOException $e) {
        // 失敗した場合、ロールバックしてエラーメッセージを出力
        $dbh->rollBack();
        echo 'エラーが発生しました: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>問題編集</title>
</head>

<body>
    <main>
        <div class="container">
            <h1>問題編集</h1>

            <form action="edit.php?id=<?= $question_id ?>" method="POST" enctype="multipart/form-data">
                <!-- CSRFトークンの埋め込み -->
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                <!-- 隠しフィールドで問題IDを渡す -->
                <input type="hidden" name="question_id" value="<?= isset($question_id) ? $question_id : '' ?>">

                <p>問題文</p>
                <input type="text" id="question" name="content" value="<?= isset($question['content']) ? htmlspecialchars($question['content'], ENT_QUOTES, 'UTF-8') : '' ?>">

                <p>選択肢:</p>
                <?php if (isset($choices) && is_array($choices)) : ?>
                    <?php foreach ($choices as $key => $choice) : ?>
                        <input type="text" name="choices[]" placeholder="選択肢を入力してください" value="<?= isset($choice["name"]) ? htmlspecialchars($choice["name"], ENT_QUOTES, 'UTF-8') : '' ?>">
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- 正解の選択肢 -->
                <p>正解の選択肢：</p>
                <?php if (isset($choices) && is_array($choices)) : ?>
                    <?php foreach ($choices as $key => $choice) : ?>

                        <input type="radio" name="correct_choice" value="<?= $key ?>" id="choice<?= $key ?>" <?= isset($choice['valid']) && $choice['valid'] ? 'checked' : '' ?>>
                        <label for="choice<?= $key + 1 ?>"><?= $key + 1 ?></label>
                    <?php endforeach; ?>
                <?php endif; ?>

                <p>問題の画像</p>
                <input type="file" name="image">

                <!-- 補足 -->
                <p>補足</p>
                <input type="text" name="supplement" value="<?= isset($question["supplement"]) ? htmlspecialchars($question["supplement"], ENT_QUOTES, 'UTF-8') : '' ?>">

                <input type="submit" value="更新">
            </form>
        </div>
    </main>
</body>

</html>
