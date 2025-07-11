<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome Dashboard</title> <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; /* Modern font */
            background-color: #e9ecef;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            color: #343a40; /* Darker text for readability */
        }
        .dashboard-container {
            background-color: #fff;
            padding: 40px;
            border-radius: 12px; /* Slightly more rounded */
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08); /* More subtle shadow */
            text-align: center;
            width: 100%;
            max-width: 450px; /* Slightly wider container */
            box-sizing: border-box; /* Include padding in width */
        }
        h2 {
            color: #007bff; /* Primary blue heading */
            margin-bottom: 35px; /* More space below heading */
            font-weight: 600;
            font-size: 1.8em;
        }
        .button-group button {
            display: block;
            width: 90%; /* Make buttons slightly wider */
            padding: 16px; /* Increased padding for better clickability */
            margin: 20px auto;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); /* Stronger button shadow */
            font-weight: 500;
        }
        .button-group button:hover {
            transform: translateY(-3px); /* Slightly more pronounced lift */
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15); /* Enhanced shadow on hover */
        }
        .button-group button:active {
            transform: translateY(0); /* Return to original position on click */
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); /* Reset shadow on click */
        }

        /* Button specific colors */
        .employee-btn {
            background-color: #007bff; /* Bootstrap primary blue */
            color: white;
        }
        .employee-btn:hover {
            background-color: #0056b3;
        }
        .client-btn {
            background-color: #28a745; /* Bootstrap success green */
            color: white;
        }
        .client-btn:hover {
            background-color: #218838;
        }
        .add-product-btn { /* New button style */
            background-color: #6c757d; /* Bootstrap secondary grey */
            color: white;
        }
        .add-product-btn:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <h2>Welcome to the Dashboard</h2>
        <div class="button-group">
            <button class="employee-btn" onclick="location.href='employee.php'">Employee Login</button>
            <button class="client-btn" onclick="location.href='client_login.php'">Client Login</button>
            <button class="add-product-btn" onclick="location.href='add_products.php'">Add Product</button>
        </div>
    </div>
</body>
</html>