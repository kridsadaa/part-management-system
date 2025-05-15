<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ไม่มีสิทธิ์เข้าถึง - I-WiS</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .access-denied-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            text-align: center;
            padding: 20px;
        }
        
        .access-denied-icon {
            font-size: 80px;
            color: #e60012;
            margin-bottom: 20px;
        }
        
        .access-denied-title {
            font-size: 28px;
            margin-bottom: 15px;
            color: #333;
        }
        
        .access-denied-message {
            font-size: 18px;
            margin-bottom: 30px;
            color: #666;
            max-width: 600px;
        }
        
        .back-button {
            background-color: #e60012;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        
        .back-button:hover {
            background-color: #c50010;
        }
    </style>
</head>
<body>
    <?php
    // Start session if not already started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    ?>
    
    <div class="access-denied-container">
        <div class="access-denied-icon">
            <i class="fas fa-exclamation-circle"></i>
        </div>
        <h1 class="access-denied-title">ไม่มีสิทธิ์เข้าถึง</h1>
        <p class="access-denied-message">
            ขออภัย คุณไม่มีสิทธิ์เข้าถึงหน้านี้ กรุณาติดต่อผู้ดูแลระบบหากคุณเชื่อว่านี่เป็นข้อผิดพลาด
        </p>
        <a href="index.php" class="back-button">
            <i class="fas fa-arrow-left"></i> กลับไปยังหน้าหลัก
        </a>
    </div>
</body>
</html>
