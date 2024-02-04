<?php
$dsn = 'mysql:host=db;dbname=posse;charset=utf8';
$user = 'root';
$password = 'root';

//try {
    // $dbh = new PDO($dsn, $user, $password);
//     echo 'Connection success!';
//     // SQL ステートメント
    // $sql = 'SELECT id, content, supplement FROM questions';
    // PDO::query() を使用してデータを取得
    // $result = $dbh->query($sql);

//     // 結果を取得して表示
//     foreach ($result as $row) {
//         echo "ID: {$row['id']}, Content: {$row['content']}, Supplement: {$row['supplement']}<br>";
//     }
    
//}

try {
    $dbh = new PDO($dsn, $user, $password);
    // echo 'Connection success!';
    $sql = 'SELECT id, content, supplement FROM questions';
    //PDO::query() を使用してデータを取得
    $result = $dbh->query($sql);
    
    // クイズの質問と選択肢を組み合わせて取得
    $questions = $dbh->query("SELECT * FROM questions")->fetchAll(PDO::FETCH_ASSOC);
    $choices = $dbh->query("SELECT * FROM choices")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($questions as $qKey => $question) {
        $question["choices"] = [];
        foreach ($choices as $cKey => $choice) {
            if ($choice["question_id"] == $question["id"]) {
                $question["choices"][] = $choice;
            }
        }
        $questions[$qKey] = $question;
    }
    
     // $questions変数の中身を確認
    // var_dump($questions);
    // その他の処理やクエリをここに追加
    
} 

catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
} finally {
    // PDO 接続を閉じる
    $dbh = null;
}
