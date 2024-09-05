<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Template Email</title>
</head>
<body>
    <h1>Hello, {{ $data['name'] }}</h1>
    <p>We are sending you this bulk mail as part of the {{ $data['campaign_name'] }} campaign.</p>
    <p>Thank you!</p>
</body>
</html>
