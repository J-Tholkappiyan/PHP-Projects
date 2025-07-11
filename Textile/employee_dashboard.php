<?php
// Error reporting for development (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start(); // Start session to manage login state

// Check if employee is logged in. If not, redirect to login page.
if (!isset($_SESSION['employee_logged_in']) || $_SESSION['employee_logged_in'] !== true) {
    header('Location: login.php'); // Redirect to login page
    exit();
}

$message = '';
// Retrieve and clear any form messages set in employee.php or other pages
if (isset($_SESSION['form_message'])) {
    $message = $_SESSION['form_message'];
    unset($_SESSION['form_message']);
}

// --- Handle Product Deletion ---
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $productIdToDelete = htmlspecialchars($_GET['id']);
    $productsFilePath = 'products.json';
    $products = [];

    if (file_exists($productsFilePath)) {
        $jsonData = file_get_contents($productsFilePath);
        $products = json_decode($jsonData, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $products = []; // Reset if JSON is invalid
        }
    }

    $productFound = false;
    $updatedProducts = [];
    $imagesToDelete = [];

    foreach ($products as $product) {
        if ($product['id'] === $productIdToDelete) {
            $productFound = true;
            // Collect image filenames for deletion
            if (!empty($product['images'])) {
                // Adapt to the new structure if it's an array of objects
                if (is_array($product['images'][0]) && isset($product['images'][0]['unique_id'])) {
                    foreach($product['images'] as $img_data) {
                        $imagesToDelete[] = $img_data['unique_id'];
                    }
                } else { // Old string format
                    $imagesToDelete = $product['images'];
                }
            }
        } else {
            $updatedProducts[] = $product; // Keep products not being deleted
        }
    }

    if ($productFound) {
        // Update products.json
        file_put_contents($productsFilePath, json_encode($updatedProducts, JSON_PRETTY_PRINT));

        // Delete product HTML file
        $productHtmlFile = 'products/' . $productIdToDelete . '.html';
        if (file_exists($productHtmlFile)) {
            unlink($productHtmlFile);
        }

        // Delete product images
        foreach ($imagesToDelete as $image) {
            $imagePath = 'product_images/' . $image;
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
        $_SESSION['form_message'] = '<div class="message success">Product deleted successfully!</div>';
    } else {
        $_SESSION['form_message'] = '<div class="message error">Product not found for deletion.</div>';
    }
    // Redirect to clear GET parameters and display message
    header('Location: employee_dashboard.php?status=deleted');
    exit();
}


// Load products for display
$products = [];
$productsFilePath = 'products.json';
if (file_exists($productsFilePath)) {
    $jsonData = file_get_contents($productsFilePath);
    $products = json_decode($jsonData, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $products = []; // Reset if JSON is invalid
    }
}

// Initialize variable to hold the ID of the updated product for scrolling
$updatedProductId = null;

// Display status message if redirected (e.g., after deletion or update)
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'deleted') {
        $message = '<div class="message success">Product deleted successfully!</div>';
    } elseif ($_GET['status'] === 'updated') {
        $message = '<div class="message success">Product updated successfully!</div>';
        if (isset($_GET['updated_id'])) {
            $updatedProductId = htmlspecialchars($_GET['updated_id']);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; /* Slightly more modern font */
            background-color: #e9ecef; /* Lighter background */
            margin: 0;
            padding: 20px;
            color: #343a40; /* Darker text for better readability */
            line-height: 1.6;
        }
        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 12px; /* Slightly more rounded corners */
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08); /* More subtle shadow */
            max-width: 1000px; /* Increased max-width for better table display */
            margin: 20px auto;
            box-sizing: border-box; /* Include padding in element's total width and height */
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 20px;
            border-bottom: 1px solid #dee2e6; /* Lighter border */
            margin-bottom: 30px;
        }
        h2 {
            color: #007bff; /* Primary blue for headings */
            margin: 0;
            font-weight: 600; /* Slightly bolder */
        }
        .message {
            margin-bottom: 20px;
            padding: 12px;
            border-radius: 8px; /* More rounded message boxes */
            font-weight: bold;
            text-align: center;
            opacity: 0.95; /* Slightly transparent */
            transition: opacity 0.3s ease; /* Smooth transition */
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        /* Dashboard specific styles */
        .dashboard-buttons {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            justify-content: flex-end;
        }
        .dashboard-buttons .btn {
            padding: 10px 18px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 1em;
            transition: background-color 0.3s ease, transform 0.2s ease;
            cursor: pointer;
            font-weight: 500;
            display: inline-flex; /* Use flex to align text and potentially icons */
            align-items: center;
            justify-content: center;
        }
        .dashboard-buttons .btn:hover {
            transform: translateY(-2px);
        }
        .dashboard-buttons .btn:active {
            transform: translateY(0);
        }
        .add-product-btn {
            background-color: #28a745;
            color: white;
        }
        .add-product-btn:hover {
            background-color: #218838;
        }
        .logout-btn {
            background-color: #dc3545;
            color: white;
        }
        .logout-btn:hover {
            background-color: #c82333;
        }

        .product-table {
            width: 100%;
            border-collapse: separate; /* Use separate to allow border-radius on cells (if needed) */
            border-spacing: 0; /* Remove space between cells */
            margin-top: 20px;
            border: 1px solid #e0e0e0; /* Overall table border */
            border-radius: 8px; /* Rounded corners for the whole table */
            overflow: hidden; /* Ensures rounded corners clip content */
        }
        .product-table th, .product-table td {
            padding: 14px 18px; /* More padding */
            text-align: left;
            border-bottom: 1px solid #e0e0e0; /* Only bottom border for inner cells */
        }
        .product-table th {
            background-color: #f0f0f0; /* Lighter header background */
            color: #495057;
            font-weight: 600;
            text-transform: uppercase; /* Uppercase headers */
            font-size: 0.9em;
        }
        .product-table tr:last-child td {
            border-bottom: none; /* No bottom border for the last row */
        }
        .product-table tr:nth-child(even) {
            background-color: #fcfcfc; /* Very subtle zebra striping */
        }
        .product-table tr:hover {
            background-color: #f5f5f5; /* Light hover effect */
        }
        /* Style for the highlighted row */
        .product-table tr.highlight {
            animation: highlightFade 3s ease-out forwards;
            /* Optionally, an initial background color for immediate feedback */
            background-color: #e0f2f7; /* A light blue for highlighting */
        }

        @keyframes highlightFade {
            0% { background-color: #e0f2f7; } /* Start with highlight color */
            100% { background-color: transparent; } /* Fade to transparent (or the row's default) */
        }


        /* --- KEY IMPROVEMENT FOR ACTIONS COLUMN --- */
        .product-table .actions {
            white-space: nowrap;
            display: flex; /* Use flexbox for button alignment */
            gap: 8px; /* Space between buttons */
            align-items: center; /* Vertically align buttons */
            justify-content: flex-start; /* Align buttons to the start of the cell */
        }
        /* --- END KEY IMPROVEMENT --- */

        .product-table .action-btn {
            padding: 8px 12px;
            border: none;
            border-radius: 6px; /* Slightly more rounded buttons */
            text-decoration: none;
            color: white;
            cursor: pointer;
            font-size: 0.9em;
            transition: background-color 0.3s ease, transform 0.2s ease;
            display: inline-block; /* Ensure padding and margin work as expected */
            font-weight: 500;
        }
        .product-table .action-btn:hover {
            transform: translateY(-1px); /* Subtle lift on hover */
        }
        .product-table .edit-btn {
            background-color: #ffc107; /* Amber/Yellow */
            color: #343a40; /* Dark text for contrast */
        }
        .product-table .edit-btn:hover {
            background-color: #e0a800;
        }
        .product-table .delete-btn {
            background-color: #dc3545; /* Red */
        }
        .product-table .delete-btn:hover {
            background-color: #c82333;
        }
        .product-table img {
            max-width: 70px; /* Slightly smaller thumbnails */
            height: auto;
            display: block;
            border-radius: 4px; /* Slight border radius for images */
            border: 1px solid #eee;
        }
        .no-products-msg {
            text-align: center;
            font-size: 1.2em;
            color: #6c757d; /* Muted grey */
            margin-top: 50px;
            padding: 20px;
            background-color: #fcfcfc;
            border-radius: 8px;
            border: 1px dashed #e0e0e0;
        }

        /* Responsive adjustments for smaller screens */
        @media (max-width: 768px) {
            .container {
                padding: 15px;
                margin: 10px auto;
            }
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            .dashboard-buttons {
                width: 100%;
                justify-content: space-around;
            }
            .dashboard-buttons .btn {
                flex-grow: 1; /* Buttons take equal width */
                text-align: center;
            }
            .product-table, .product-table thead, .product-table tbody, .product-table th, .product-table td, .product-table tr {
                display: block; /* Make table elements behave like blocks */
            }
            .product-table thead tr {
                position: absolute;
                top: -9999px; /* Hide table headers */
                left: -9999px;
            }
            .product-table tr {
                border: 1px solid #ddd;
                margin-bottom: 15px;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            }
            .product-table td {
                border: none;
                border-bottom: 1px solid #eee;
                position: relative;
                padding-left: 50%; /* Space for pseudo-element labels */
                text-align: right;
            }
            .product-table td:last-child {
                border-bottom: none;
            }
            .product-table td::before { /* Add pseudo-element for mobile labels */
                content: attr(data-label);
                position: absolute;
                left: 10px;
                width: 45%;
                padding-right: 10px;
                white-space: nowrap;
                text-align: left;
                font-weight: bold;
                color: #555;
            }
            .product-table .actions {
                justify-content: flex-end; /* Align action buttons to the right on mobile */
                padding-top: 10px;
                border-top: 1px solid #eee;
                margin-top: 10px;
            }
            .product-table img {
                float: right; /* Align image to the right on mobile */
                margin-left: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Employee Dashboard</h2>
            <div class="dashboard-buttons">
                <a href="add_products.php" class="btn add-product-btn">Add New Product</a>
                <a href="logout.php?type=employee" class="btn logout-btn">Logout</a>
            </div>
        </div>
        <?php echo $message; // Display success/error messages for product operations ?>

        <h3>All Products</h3>
        <?php if (!empty($products)): ?>
            <table class="product-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Product Name</th>
                        <th>Price</th>
                        <th>Description</th>
                        <th>Images</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product):
                        $rowClass = '';
                        if ($updatedProductId && $product['id'] === $updatedProductId) {
                            $rowClass = 'highlight'; // Add highlight class if this is the updated product
                        }
                    ?>
                        <tr id="product-<?php echo htmlspecialchars($product['id']); ?>" class="<?php echo $rowClass; ?>">
                            <td data-label="ID"><?php echo htmlspecialchars($product['id']); ?></td>
                            <td data-label="Product Name"><?php echo htmlspecialchars($product['product_name']); ?></td>
                            <td data-label="Price">₹<?php echo number_format($product['price'], 2); ?></td>
                            <td data-label="Description"><?php echo substr(htmlspecialchars($product['description']), 0, 100) . (strlen($product['description']) > 100 ? '...' : ''); ?></td>
                            <td data-label="Images">
                                <?php
                                $displayImage = '';
                                if (!empty($product['images'])) {
                                    // Handle both old string format and new object format gracefully
                                    if (is_array($product['images'][0]) && isset($product['images'][0]['unique_id'])) {
                                        $displayImage = $product['images'][0]['unique_id'];
                                    } else { // Old string format
                                        $displayImage = $product['images'][0];
                                    }
                                }
                                ?>
                                <?php if (!empty($displayImage)): ?>
                                    <img src="product_images/<?php echo htmlspecialchars($displayImage); ?>" alt="Product Image" width="50">
                                <?php else: ?>
                                    No Image
                                <?php endif; ?>
                            </td>
                            <td class="actions" data-label="Actions">
                                <a href="edit_product.php?id=<?php echo htmlspecialchars($product['id']); ?>" class="action-btn edit-btn">Edit</a>
                                <a href="employee_dashboard.php?action=delete&id=<?php echo htmlspecialchars($product['id']); ?>" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete this product? This action cannot be undone.');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="no-products-msg">No products added yet. Click "Add New Product" to get started!</p>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Check if there's an updated product ID to scroll to
            const urlParams = new URLSearchParams(window.location.search);
            const updatedId = urlParams.get('updated_id');

            if (updatedId) {
                const targetRow = document.getElementById('product-' + updatedId);
                if (targetRow) {
                    // Scroll into view
                    targetRow.scrollIntoView({ behavior: 'smooth', block: 'center' });

                    // Add a temporary highlight class to the row
                    targetRow.classList.add('highlight');
                    // The CSS animation 'highlightFade' will handle fading it out
                }
            }
        });
    </script>
</body>
</html>