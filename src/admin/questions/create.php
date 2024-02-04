<?php
require "../../vendor/autoload.php";
use Verot\Upload\Upload;

// dbconnect.phpを読み込む
require_once('../../dbconnect.php');

// PDO インスタンスの生成とエラーモードの設定
$dbh = new PDO($dsn, $user, $password);
// $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// POST データの受け取りと問題作成処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 必須項目のバリデーション
        if (empty($_POST['content']) || empty($_POST['choices']) || empty($_POST['correct_choices'])) {
            // 必須項目が空の場合のエラー処理
            echo '全ての項目を入力してください。';
            exit();
        }

        // フォームからのデータ取得
        $content = $_POST['content'];
        $choices = $_POST['choices'];
        $correct_choices = $_POST['correct_choices'];

        // 画像ファイルの処理
        $image_name = null; // 初期化

        if (!empty($_FILES['image']['name'])) {
            $file = $_FILES['image'];
            $lang = 'ja_JP';

            // アップロードされたファイルを渡す
            $handle = new Upload($file, $lang);

            if ($handle->uploaded) {
                // アップロードディレクトリを指定して保存
                $handle->process('../../assets/img/quiz/');
                if ($handle->processed) {
                    // アップロード成功
                    $image_name = $handle->file_dst_name;

                    // 画像ファイルのバリデーション
                    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                    $max_file_size = 5 * 1024 * 1024; // 5MB

                    if (!in_array(strtolower($handle->file_src_name_ext), $allowed_extensions)) {
                        echo '許可されていない拡張子です。';
                        exit();
                    }

                    if ($handle->file_src_size > $max_file_size) {
                        echo 'ファイルサイズが大きすぎます。';
                        exit();
                    }
                } else {
                    // アップロード処理失敗
                    throw new Exception($handle->error);
                }
            } else {
                // アップロード失敗
                throw new Exception($handle->error);
            }
        }

        // SQL文の作成
        $sqlInsertQuestion = 'INSERT INTO questions (content, image, supplement) VALUES (:content, :image, :supplement)';
        $stmtInsertQuestion = $dbh->prepare($sqlInsertQuestion);
        $stmtInsertQuestion->bindValue(':content', $content, PDO::PARAM_STR);
        $stmtInsertQuestion->bindValue(':image', $image_name, PDO::PARAM_STR);
        $stmtInsertQuestion->bindValue(':supplement', $_POST['supplement'], PDO::PARAM_STR);
        $stmtInsertQuestion->execute();

        // 直前に挿入した問題のIDを取得
        $questionId = $dbh->lastInsertId();

        // choices テーブルへのデータ挿入
        $sqlInsertChoices = 'INSERT INTO choices (question_id, name, valid) VALUES (:question_id, :name, :valid)';
        $stmtInsertChoices = $dbh->prepare($sqlInsertChoices);

        foreach ($choices as $index => $choice) {
            // 値のバインド
            $stmtInsertChoices->bindValue(':question_id', $questionId, PDO::PARAM_INT);
            $stmtInsertChoices->bindValue(':name', $choice, PDO::PARAM_STR);

            // 正解の選択かどうかを確認
            $isValid = in_array($index + 1, $correct_choices) ? 1 : 0;
            $stmtInsertChoices->bindValue(':valid', $isValid, PDO::PARAM_INT);

            // 実行
            $stmtInsertChoices->execute();
        }

        // リダイレクト
        header('Location: http://localhost:8080/admin/index.php');
        exit();
    } catch (Exception $e) {
        echo 'Error: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>問題作成</title>
</head>

<body>
    <div class="container">
        <h1>問題作成</h1>

        <form action="create.php" method="post" enctype="multipart/form-data">
            <p>問題文：</p>
            <input type="text" name="content" id="question" placeholder="問題文を入力してください" />

            <p>選択肢：</p>
            <input type="text" name="choices[]" placeholder="選択肢1を入力してください">
            <input type="text" name="choices[]" placeholder="選択肢2を入力してください">
            <input type="text" name="choices[]" placeholder="選択肢3を入力してください">

            <p>正解の選択肢：</p>
            <input type="radio" name="correct_choices[]" value="1" id="choice1">
            <label for="choice1">1</label>
            <input type="radio" name="correct_choices[]" value="2" id="choice2">
            <label for="choice2">2</label>
            <input type="radio" name="correct_choices[]" value="3" id="choice3">
            <label for="choice3">3</label>

            <p>問題の画像：</p>
            <input type="file" name="image">

            <p>補足：</p>
            <input type="text" name="supplement">

            <input type="submit" value="作成">
        </form>
    </div>
</body>

</html>
