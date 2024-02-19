<!DOCTYPE html>
<html>
<head>
    <title>Email Reader</title>
</head>
<body>
    <form action="read_email.php" method="post">
        <label for="server">Server:</label>
        <input type="text" id="server" name="server" required><br>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required><br>

        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required><br>

        <input type="submit" value="Connect">
    </form>
</body>
</html>

