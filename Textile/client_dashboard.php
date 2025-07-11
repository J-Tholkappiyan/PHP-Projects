<?php
session_start(); // Start session to check login state

// Check if client is logged in, otherwise redirect to login page (index.php is the common login)
if (!isset($_SESSION['client_logged_in']) || $_SESSION['client_logged_in'] !== true) {
    header('Location: login.php'); // Redirect to common login page
    exit();
}

// PHP logic to read products.json will go here
$products = [];
$productsFilePath = 'products.json';
if (file_exists($productsFilePath)) {
    $jsonData = file_get_contents($productsFilePath);
    $products = json_decode($jsonData, true);
    // Handle JSON decoding errors
    if (json_last_error() !== JSON_ERROR_NONE) {
        $products = []; // Reset if JSON is invalid
        // Optionally log the JSON error: error_log("Error decoding products.json: " . json_last_error_msg());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
            margin-bottom: 30px;
        }
        h2 {
            color: #0056b3;
            margin: 0;
        }
        .logout-btn {
            background-color: #dc3545;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 1em;
            transition: background-color 0.3s ease;
        }
        .logout-btn:hover {
            background-color: #c82333;
        }
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
        }
        .product-card {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            overflow: hidden;
            text-align: center;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between; /* Pushes button to bottom */
        }
        .product-card img {
            max-width: 100%;
            height: 180px; /* Fixed height for image previews */
            object-fit: contain; /* Ensures image fits without cropping */
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        .product-card h3 {
            font-size: 1.4em;
            color: #333;
            margin-top: 0;
            margin-bottom: 10px;
        }
        .product-card .price {
            font-size: 1.2em;
            color: #e67e22;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .product-card .description {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 15px;
            flex-grow: 1; /* Allows description to take available space */
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 3; /* Show max 3 lines */
            -webkit-box-orient: vertical;
        }
        .product-card .view-details-btn {
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: auto; /* Pushes button to bottom of the card */
        }
        .product-card .view-details-btn:hover {
            background-color: #0056b3;
        }
        .no-products {
            text-align: center;
            color: #666;
            margin-top: 50px;
            font-size: 1.2em;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h2>
        <a href="logout.php?type=client" class="logout-btn">Logout</a>
    </div>

    <h3>Available Products:</h3>
    <?php if (!empty($products)): ?>
        <div class="product-grid">
            <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <?php
                    $imageUrl = 'product_images/placeholder.png'; // Default placeholder
                    // Check if 'images' key exists and is an array
                    if (isset($product['images']) && is_array($product['images']) && !empty($product['images'])) {
                        $imageFileName = null;
                        // New format: array of objects with 'unique_id'
                        if (isset($product['images'][0]) && is_array($product['images'][0]) && isset($product['images'][0]['unique_id'])) {
                            $imageFileName = $product['images'][0]['unique_id'];
                        } 
                        // Old format: array of strings
                        else if (isset($product['images'][0]) && is_string($product['images'][0])) {
                            $imageFileName = $product['images'][0];
                        }
                        
                        // Construct image URL if a valid filename was found and the file exists
                        if (!empty($imageFileName) && file_exists('product_images/' . $imageFileName)) {
                            $imageUrl = 'product_images/' . htmlspecialchars($imageFileName);
                        }
                    }
                    ?>
                    <img src="<?php echo $imageUrl; ?>" alt="<?php echo htmlspecialchars($product['product_name'] ?? 'Product Image'); ?>">
                    <h3><?php echo htmlspecialchars($product['product_name'] ?? 'No Name'); ?></h3>
                    <p class="price">₹<?php echo number_format($product['price'] ?? 0, 2); ?></p>
                    <p class="description"><?php echo nl2br(htmlspecialchars($product['description'] ?? 'No description available.')); ?></p>
                    <a href="products/<?php echo htmlspecialchars($product['id'] ?? 'default'); ?>.html" target="_blank" class="view-details-btn">View Details</a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="no-products">No products available at the moment.</p>
    <?php endif; ?>

</body>
</html>