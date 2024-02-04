<?php

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // フォームから送信されたユーザー情報を取得
    $username = $_POST["username"];
    $email = $_POST["email"];
    $password = $_POST["password"];
    $password_conf = $_POST["password_conf"];

    // バリデーション
    if (empty($username) || empty($email) || empty($password) || empty($password_conf)) {
        $error = "全てのフィールドを入力してください";
    } elseif ($password !== $password_conf) {
        $error = "パスワードとパスワード確認が一致しません";
    } else {
        // // パスワードのハッシュ化
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        // パスワードのハッシュ化をしない
        // $hashed_password = $_POST['password'];


        // データベースへの接続
        require_once('../../dbconnect.php');
        $dbh = new PDO($dsn, $user, $password);

        // ユーザーの登録
        $sql = 'INSERT INTO users (username, email, password) VALUES (:username, :email, :password)';
        $stmt = $dbh->prepare($sql);
        $stmt->bindParam(':username', $username,);
        $stmt->bindParam(':email', $email, );
        $stmt->bindParam(':password', $hashed_password, );
        $stmt->execute();

        // PDO::PARAM_STR

        // 登録後の画面にリダイレクト
        header("Location: signin.php");
        exit();
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
    <title>新規登録</title>
</head>

<body>
    <main>
        <div class="container">
            <h1>新規登録</h1>

            <?php if (isset($error)) : ?>
                <p style="color: red;"><?= $error ?></p>
            <?php endif; ?>

            <form action="" method="post">
                <!-- ユーザー名 -->
                <div>
                    <label for="username">ユーザー名：</label>
                    <input type="text" id="username" name="username" required>
                </div>
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
                <!-- パスワード確認 -->
                <div>
                    <label for="password_conf">パスワード確認：</label>
                    <input type="password" id="password_conf" name="password_conf" required>
                </div>

                <input type="submit" value="新規登録">
            </form>
        </div>
    </main>
</body>

</html>
