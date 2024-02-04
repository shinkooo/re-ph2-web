<?php
require_once('../../dbconnect.php');

$pdo = new PDO($dsn, $user, $password);
// $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
// $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // フォームから送信されたメールアドレスとパスワードを取得
    $email = $_POST['email'];
    $password = $_POST['password'];

    try {
        // メールアドレスが一致するユーザーを取得
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // パスワードが一致するか検証
        if ($user && password_verify($password, $user['password'])) {
            // ログイン成功
            // $_SESSION['user_id'] = $user['id'];
            header('Location: http://localhost:8080/admin/index.php');
            exit();
        } else {
            // ログイン失敗
            $error = "メールアドレスまたはパスワードが正しくありません。";
        }
    } catch (PDOException $e) {
        // データベース接続エラーハンドリング
        echo 'データベースに接続できませんでした。エラー: ' . $e->getMessage();
    }
}

?>



<!-- ここからは HTML 部分 -->
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン</title>
</head>

<body>
    <main>
        <div class="container">
            <h1>ログイン</h1>

            <?php if (isset($error)) : ?>
                <p style="color: red;"><?= $error ?></p>
            <?php endif; ?>

            <form action="" method="post">
                <!-- メールアドレス -->
                <div>
                    <label for="email">メールアドレス：</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <!-- パスワード -->
                <div>
                    <label for="password">パスワード：</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <input type="submit" value="ログイン">
            </form>
        </div>
    </main>
</body>

</html>
