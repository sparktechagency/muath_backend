<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Information</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f7f7f7;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
           
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border: 1px solid #4CAF50; /* Added border */
        }
        .header {
            background-color: #4CAF50;
            color: white;
            padding: 15px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            padding: 20px;
            font-size: 16px;
            line-height: 1.6;
            color: #333;
        }
        .content h3 {
            color: #4CAF50;
        }
        .footer {
            text-align: center;
            padding: 10px;
            font-size: 12px;
            color: #777;
        }
        .footer a {
            color: #4CAF50;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Custom Order</h2>
        </div>
        <div class="content">
            <h3>Customer Information:</h3>
            <ul>
                <li><strong>Full Name:</strong> {{ $order['full_name'] }}</li>
                <li><strong>Email:</strong> {{ $order['email'] }}</li>
                <li><strong>Phone Number:</strong> {{ $order['phone_number'] }}</li>
            </ul>

            <h3>Customer Address Details:</h3>
            <ul>
                <li><strong>Address:</strong> {{ $order['address'] }}</li>
                <li><strong>City:</strong> {{ $order['city'] }}</li>
                <li><strong>State:</strong> {{ $order['state'] }}</li>
                <li><strong>Zip Code:</strong> {{ $order['zip_code'] }}</li>
                <li><strong>Country:</strong> {{ $order['country'] }}</li>
            </ul>

            <h3>Order Information:</h3>
            <p><strong>Order Date:</strong> {{ $order['date'] }}</p>
            <p><strong>Order Description:</strong> {{ $order['description'] }}</p>
        </div>
        <div class="footer">
            <p>&copy; 2025 Your Company. All rights reserved.</p>
            <p>If you have any questions, feel free to <a href="mailto:support@yourcompany.com">contact us</a>.</p>
        </div>
    </div>
</body>
</html>
