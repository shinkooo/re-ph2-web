<?php
// //ログインができていない場合はサインインさせる
// if (isset($_SESSION['id'])) { //ログインしているとき
// } else { //ログインしていない時
// header("Location: http://localhost:8080/admin/auth/signin.php");
// }

// dbconnect.phpを読み込む
require_once('../dbconnect.php');
$dbh = new PDO($dsn, $user, $password);

try {
    // クエリの実行
    $sqlGetQuestions = 'SELECT * FROM questions';
    $stmtGetQuestions = $dbh->prepare($sqlGetQuestions);
    $stmtGetQuestions->execute();
    $questions = $stmtGetQuestions->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // エラーハンドリング
    echo 'データの取得に失敗しました: ' . $e->getMessage();
} finally {
    // PDO 接続を閉じる
    $dbh = null;
}

?>

<?php if (isset($questions) && !empty($questions)) : ?>

    <!DOCTYPE html>
    <html lang="ja">

    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>問題一覧</title>
    </head>

    <body>

        <!-- メニュー欄 -->
        <nav>
            <ul>
                <li><a href="http://localhost:8080/admin/index.php">問題一覧</a></li>
                <li><a href="http://localhost:8080/admin/questions/create.php">問題作成</a></li>
                <!-- 他のメニュー項目も追加 -->
            </ul>
        </nav>
        <h1>問題一覧</h1>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>問題内容</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($questions as $index => $question) : ?>
                    <tr>
                        <td><?= $question["id"] ?></td>
                        <td><a href="./questions/edit.php?id=<?= $question["id"] ?>"><?= $question["content"] ?></a></td>
                        <td>
                            <!-- 問題削除フォーム -->
                            <form method="POST" action="index.php" onsubmit="return confirm('本当に削除しますか？');">
                                <input type="hidden" name="id" value="<?= $question['id'] ?>">
                                <button type="submit" name="deleteQuestion">削除</button>
                            </form>

                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php
        // 以下に提供されたコードを追加
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deleteQuestion'])) {
            // 削除フォームが送信された場合の処理
            try {
                // 削除対象の問題のIDを取得
                $questionId = $_POST['id'];

                // トランザクションを開始
                $dbh = new PDO($dsn, $user, $password);
                $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $dbh->beginTransaction();

                // choicesテーブルから削除
                $deleteChoicesSql = "DELETE FROM choices WHERE question_id = :id";
                $stmt = $dbh->prepare($deleteChoicesSql);
                $stmt->bindParam(':id', $questionId, PDO::PARAM_INT);
                $stmt->execute();

                // questionsテーブルから削除
                $deleteQuestionSql = "DELETE FROM questions WHERE id = :id";
                $stmt = $dbh->prepare($deleteQuestionSql);
                $stmt->bindParam(':id', $questionId, PDO::PARAM_INT);
                $stmt->execute();

                // トランザクションをコミット
                $dbh->commit();
            } catch (PDOException $e) {
                // エラーが発生した場合はロールバック
                $dbh->rollBack();
                echo '削除に失敗しました: ' . $e->getMessage();
            } finally {
                // PDO 接続を閉じる
                $dbh = null;
            }
        }
        ?>
    <?php else : ?>
        <p>問題がありません。</p>
    <?php endif; ?>

    </body>

    </html>