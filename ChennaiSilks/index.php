<?php
// PHP logic to handle form submission
$message = '';

// Define directories for images, HTML files, and JSON data
$uploadImageDir = 'product_images/';
$uploadHtmlDir = 'product_html/';
$jsonFile = 'products.json'; // File to store product data

// Ensure necessary directories exist and are writable
if (!is_dir($uploadImageDir)) {
    mkdir($uploadImageDir, 0777, true); // 0777 for full permissions, adjust for production
}
if (!is_dir($uploadHtmlDir)) {
    mkdir($uploadHtmlDir, 0777, true); // 0777 for full permissions, adjust for production
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- Input Sanitization and Validation ---
    $productName = trim($_POST['productName'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $description = trim($_POST['description'] ?? '');

    // Validate Product Name (only letters, numbers, and spaces)
    if (!preg_match('/^[a-zA-Z0-9\s]+$/', $productName)) {
        $message = '<div style="color: red; padding: 10px; background-color: #ffe0e0; border: 1px solid #ff9999; border-radius: 5px; margin-bottom: 15px;">Error: Product Name can only contain letters, numbers, and spaces.</div>';
    }
    // Validate Price (must be a non-negative number)
    elseif (!is_numeric($price) || (float)$price < 0) {
        $message = '<div style="color: red; padding: 10px; background-color: #ffe0e0; border: 1px solid #ff9999; border-radius: 5px; margin-bottom: 15px;">Error: Price must be a positive number.</div>';
    } else {
        // Generate a unique numeric Product ID (using timestamp for simplicity)
        // In a real-world application, consider a more robust ID generation system (e.g., database auto-increment).
        $productId = time();

        // --- Image Upload Handling ---
        $uploadedImages = [];
        if (isset($_FILES['productImages']) && is_array($_FILES['productImages']['name'])) {
            // Loop through each uploaded file
            foreach ($_FILES['productImages']['error'] as $key => $error) {
                if ($error == UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES['productImages']['tmp_name'][$key];
                    $original_name = $_FILES['productImages']['name'][$key];
                    $file_extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
                    $file_size = $_FILES['productImages']['size'][$key]; // Get file size in bytes

                    // --- Image Size Validation (200KB limit) ---
                    $max_file_size = 200 * 1024; // 200 KB in bytes
                    if ($file_size > $max_file_size) {
                        $message .= '<div style="color: orange; padding: 10px; background-color: #fff3e0; border: 1px solid #ffcc99; border-radius: 5px; margin-bottom: 15px;">Warning: Image ' . htmlspecialchars($original_name) . ' is too large (' . round($file_size / 1024, 2) . ' KB). Max allowed is 200 KB. Skipping.</div>';
                        continue; // Skip this file
                    }

                    // Generate a unique filename to prevent overwrites and clean filenames
                    $new_file_name = uniqid('img_', true) . '.' . $file_extension;
                    $targetFile = $uploadImageDir . $new_file_name;

                    // Validate image type (basic check)
                    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                    if (!in_array($file_extension, $allowed_extensions)) {
                        $message .= '<div style="color: orange; padding: 10px; background-color: #fff3e0; border: 1px solid #ffcc99; border-radius: 5px; margin-bottom: 15px;">Warning: File ' . htmlspecialchars($original_name) . ' is not a valid image type. Skipping.</div>';
                        continue;
                    }

                    // Move the uploaded file
                    if (move_uploaded_file($tmp_name, $targetFile)) {
                        $uploadedImages[] = $targetFile; // Store path relative to index.php
                    } else {
                        $message .= '<div style="color: orange; padding: 10px; background-color: #fff3e0; border: 1px solid #ffcc99; border-radius: 5px; margin-bottom: 15px;">Warning: Failed to upload image ' . htmlspecialchars($original_name) . '.</div>';
                    }
                } elseif ($error != UPLOAD_ERR_NO_FILE) {
                    // Handle other upload errors besides no file chosen
                    $message .= '<div style="color: orange; padding: 10px; background-color: #fff3e0; border: 1px solid #ffcc99; border-radius: 5px; margin-bottom: 15px;">Warning: Error uploading file. Code: ' . $error . '.</div>';
                }
            }
        }

        // Prepare product data for JSON and HTML generation
        $productData = [
            'productId' => $productId,
            'productName' => $productName,
            'price' => (float)$price,
            'description' => $description,
            'images' => $uploadedImages // Store paths to images relative to index.php
        ];

        // --- Save Product Data to JSON File ---
        $products = [];
        if (file_exists($jsonFile)) {
            $currentJsonContent = file_get_contents($jsonFile);
            if (!empty($currentJsonContent)) {
                $decodedProducts = json_decode($currentJsonContent, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decodedProducts)) {
                    $products = $decodedProducts;
                } else {
                    error_log("Error decoding products.json: " . json_last_error_msg());
                    // Optionally, delete the malformed JSON file to start fresh or alert administrator
                }
            }
        }
        $products[] = $productData; // Add the new product
        file_put_contents($jsonFile, json_encode($products, JSON_PRETTY_PRINT));

        // --- Generate Product HTML File ---
        $productHtmlFileName = $productId . '.html'; // HTML file name based on Product ID
        $productHtmlFilePath = $uploadHtmlDir . $productHtmlFileName; // Full path to save

        $htmlContent = "
            <!DOCTYPE html>
            <html lang='en'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>Product Details: " . htmlspecialchars($productName) . "</title>
                <style>
                    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 40px; background-color: #eef2f7; color: #333; }
                    .product-container { background-color: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); max-width: 900px; margin: 30px auto; display: flex; flex-wrap: wrap; gap: 30px; }
                    .product-header { width: 100%; text-align: center; margin-bottom: 30px; color: #007bff; font-size: 2.5em; border-bottom: 2px solid #007bff; padding-bottom: 15px; }
                    .details-section { flex: 1; min-width: 300px; }
                    .details-section h2 { color: #555; margin-bottom: 20px; font-size: 1.8em; }
                    .detail-item { margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px dashed #eee; }
                    .detail-item:last-child { border-bottom: none; }
                    .detail-label { font-weight: bold; color: #007bff; display: block; margin-bottom: 5px; font-size: 1.1em; }
                    .detail-value { font-size: 1.05em; line-height: 1.6; color: #444; }
                    .description-box { background-color: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px solid #dee2e6; margin-top: 10px; }

                    .image-gallery-section { flex: 1.5; min-width: 350px; display: flex; flex-direction: column; align-items: center; }
                    .image-gallery-section h2 { color: #555; margin-bottom: 20px; font-size: 1.8em; }
                    .image-gallery { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; width: 100%; }

                    /* Styles for the individual image container for zoom */
                    .img-magnifier-container {
                        position: relative; /* Needed for positioning the lens */
                        width: 100%;
                        /* Setting a flexible height to allow images to fit without cropping */
                        height: 200px; /* Fixed height for product images on detail page */
                        overflow: hidden; /* Hide parts of image if it overflows */
                        border: 3px solid #ddd;
                        border-radius: 8px;
                        box-shadow: 0 1px 5px rgba(0,0,0,0.05);
                        cursor: crosshair; /* Indicates a specific point selection */
                        display: flex; /* Use flexbox to center image vertically/horizontally */
                        justify-content: center;
                        align-items: center;
                    }
                    .img-magnifier-container img {
                        width: 100%;
                        height: 100%;
                        /* object-fit: contain ensures the entire image is visible, maintaining aspect ratio */
                        object-fit: contain;
                        display: block;
                    }

                    /* Styles for the magnifying glass lens */
                    .img-magnifier-lens {
                        position: absolute;
                        border: 3px solid #007bff; /* Lens border */
                        width: 100px; /* Size of the lens */
                        height: 100px; /* Size of the lens */
                        border-radius: 50%; /* Make it circular */
                        background-repeat: no-repeat;
                        background-color: rgba(255, 255, 255, 0.7); /* Slightly transparent background */
                        box-shadow: 0 0 10px rgba(0,0,0,0.3);
                        pointer-events: none; /* Allows mouse events to pass through to the image below */
                        display: none; /* Hidden by default */
                        z-index: 99; /* Ensure it's above other content */
                    }

                    @media (max-width: 768px) {
                        .product-container { flex-direction: column; padding: 20px; }
                        .image-gallery { grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); }
                        .img-magnifier-lens {
                            width: 80px; /* Smaller lens for mobile */
                            height: 80px;
                        }
                    }
                </style>
            </head>
            <body>
                <div class='product-container'>
                    <div class='product-header'>Product Details</div>

                    <div class='details-section'>
                        <h2>Product Information</h2>
                        <div class='detail-item'>
                            <span class='detail-label'>Product ID:</span>
                            <span class='detail-value'>" . htmlspecialchars($productId) . "</span>
                        </div>
                        <div class='detail-item'>
                            <span class='detail-label'>Product Name:</span>
                            <span class='detail-value'>" . htmlspecialchars($productName) . "</span>
                        </div>
                        <div class='detail-item'>
                            <span class='detail-label'>Price:</span>
                            <span class='detail-value'>Rs. " . htmlspecialchars(number_format($price, 2)) . "</span>
                        </div>
                        <div class='detail-item'>
                            <span class='detail-label'>Description:</span>
                            <div class='detail-value description-box'>" . nl2br(htmlspecialchars($description)) . "</div>
                        </div>
                    </div>

                    <div class='image-gallery-section'>
                        <h2>Product Images</h2>
                        <div class='image-gallery'>";
        if (!empty($productData['images'])) {
            foreach ($productData['images'] as $image) {
                // Image path needs to be relative to the HTML file (product_html/productId.html -> ../product_images/image.jpg)
                $relativeImagePath = '../' . htmlspecialchars($image); // Ensure path is HTML safe
                $htmlContent .= "
                                <div class='img-magnifier-container'>
                                    <img id='img-" . $productId . "-" . uniqid() . "' src='" . $relativeImagePath . "' alt='" . htmlspecialchars($productName) . " Image'>
                                </div>";
            }
        } else {
            $htmlContent .= "<p style='width: 100%; text-align: center; color: #888;'>No images available for this product.</p>";
        }
        $htmlContent .= "
                        </div>
                    </div>
                </div>

                <script>
                    function magnify(imgID, zoom) {
                        var img, lens, cx, cy;
                        img = document.getElementById(imgID);

                        // If image or lens doesn't exist, or already initialized
                        if (!img || img.magnifierInitialized) return;

                        /* Create lens: */
                        lens = document.createElement('DIV');
                        lens.setAttribute('class', 'img-magnifier-lens');
                        /* Insert lens: */
                        img.parentElement.insertBefore(lens, img);

                        /* Calculate the ratio between result DIV and lens: */
                        // Use img.naturalWidth/Height for true image dimensions for accurate zoom
                        cx = lens.offsetWidth / img.offsetWidth;
                        cy = lens.offsetHeight / img.offsetHeight;

                        /* Set background properties for the lens: */
                        lens.style.backgroundImage = 'url(\"' + img.src + '\")';
                        lens.style.backgroundRepeat = 'no-repeat';
                        // Adjust background size based on natural image dimensions and zoom
                        lens.style.backgroundSize = (img.naturalWidth * zoom) + 'px ' + (img.naturalHeight * zoom) + 'px';


                        /* Store references to the lens on the image for proper cleanup/control */
                        img.magnifierLens = lens;
                        img.magnifierInitialized = true; // Mark as initialized

                        /* Execute a function when someone moves the cursor over the image, or the lens: */
                        img.addEventListener('mousemove', moveMagnifier);
                        lens.addEventListener('mousemove', moveMagnifier);
                        /* And also for touch screens: */
                        img.addEventListener('touchmove', moveMagnifier);
                        lens.addEventListener('touchmove', moveMagnifier);

                        img.addEventListener('mouseover', showMagnifier);
                        lens.addEventListener('mouseover', showMagnifier);
                        img.addEventListener('mouseout', hideMagnifier);
                        lens.addEventListener('mouseout', hideMagnifier);

                        // Also for touch screens, simple show/hide based on touch
                        img.addEventListener('touchstart', showMagnifier);
                        img.addEventListener('touchend', hideMagnifier); // Hide when touch ends

                        function getCursorPos(e) {
                            var a, x = 0, y = 0;
                            e = e || window.event;
                            /* Get the x and y positions of the image: */
                            a = img.getBoundingClientRect();
                            /* Calculate the cursor's x and y coordinates, relative to the image: */
                            x = e.pageX - a.left;
                            y = e.pageY - a.top;
                            /* Consider any page scrolling: */
                            x = x - window.pageXOffset;
                            y = y - window.pageYOffset;
                            return {x : x, y : y};
                        }

                        function moveMagnifier(e) {
                            var pos, x, y;
                            /* Prevent any other actions that may occur when moving over the image */
                            e.preventDefault();

                            // Use changedTouches for touch events
                            if (e.type.startsWith('touch') && e.changedTouches && e.changedTouches.length > 0) {
                                pos = getCursorPos(e.changedTouches[0]);
                            } else {
                                pos = getCursorPos(e);
                            }

                            x = pos.x;
                            y = pos.y;

                            /* Prevent the lens from being outside the image boundaries relative to lens size */
                            var lensHalfWidth = lens.offsetWidth / 2;
                            var lensHalfHeight = lens.offsetHeight / 2;

                            if (x > img.width - lensHalfWidth) {x = img.width - lensHalfWidth;}
                            if (x < lensHalfWidth) {x = lensHalfWidth;}
                            if (y > img.height - lensHalfHeight) {y = img.height - lensHalfHeight;}
                            if (y < lensHalfHeight) {y = lensHalfHeight;}

                            /* Set the position of the lens: */
                            lens.style.left = (x - lensHalfWidth) + 'px';
                            lens.style.top = (y - lensHalfHeight) + 'px';

                            /* Display what the lens sees: */
                            // Adjust background position based on zoom and lens center
                            // Here, we use the actual rendered width/height of the image element
                            // to calculate the background position accurately for the magnifier
                            let bgPosX = ((x * zoom) - lensHalfWidth) * (img.naturalWidth / img.width);
                            let bgPosY = ((y * zoom) - lensHalfHeight) * (img.naturalHeight / img.height);
                            lens.style.backgroundPosition = '-' + bgPosX + 'px -' + bgPosY + 'px';
                        }

                        function showMagnifier() {
                            lens.style.display = 'block';
                        }

                        function hideMagnifier() {
                            lens.style.display = 'none';
                        }
                    }

                    // Apply magnifier to all product images in the gallery
                    window.addEventListener('load', () => {
                        document.querySelectorAll('.img-magnifier-container img').forEach(imgElement => {
                            // Ensure the image has loaded to get correct dimensions for zoom calculation
                            if (imgElement.complete) {
                                magnify(imgElement.id, 2.5); // '2.5' is the zoom factor (2.5x zoom)
                            } else {
                                imgElement.addEventListener('load', () => magnify(imgElement.id, 2.5));
                            }
                        });
                    });
                </script>
            </body>
            </html>
        ";

        file_put_contents($productHtmlFilePath, $htmlContent);

        // Redirect to dashboard.html after successful product addition
        header("Location: dashboard.html");
        exit(); // Important to stop script execution after redirect
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Product</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            box-sizing: border-box;
        }
        .container {
            background-color: #ffffff;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
            max-width: 650px;
            width: 100%;
            box-sizing: border-box;
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.2em;
            border-bottom: 2px solid #eee;
            padding-bottom: 15px;
        }
        .form-group {
            margin-bottom: 25px;
        }
        label {
            display: block;
            margin-bottom: 10px;
            font-weight: bold;
            color: #555;
            font-size: 1.05em;
        }
        input[type="text"],
        input[type="number"],
        textarea {
            width: calc(100% - 24px); /* Account for padding and border */
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 1em;
            box-sizing: border-box;
            transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }
        input[type="text"]:focus,
        input[type="number"]:focus,
        textarea:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
            outline: none;
        }
        textarea {
            resize: vertical;
            min-height: 120px;
        }
        input[type="file"] {
            display: none; /* Hide the default file input */
        }
        .custom-file-upload {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            border: none;
            transition: background-color 0.2s ease-in-out;
        }
        .custom-file-upload:hover {
            background-color: #0056b3;
        }

        .image-preview-container {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 15px;
            border: 1px dashed #ced4da;
            padding: 10px;
            border-radius: 8px;
            min-height: 80px; /* Give some height even if empty */
            align-items: flex-start;
        }
        .image-preview {
            position: relative;
            width: 90px;
            height: 90px;
            border: 1px solid #ddd;
            border-radius: 6px;
            overflow: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #f8f9fa;
        }
        .image-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain; /* object-fit: contain ensures the entire image is visible within the preview box */
            display: block;
        }
        .image-preview .remove-btn {
            position: absolute;
            top: 3px;
            right: 3px;
            background-color: rgba(220, 53, 69, 0.85); /* Bootstrap red */
            color: white;
            border: none;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            font-size: 0.8em;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: bold;
            line-height: 1;
            transition: background-color 0.2s ease-in-out;
        }
        .image-preview .remove-btn:hover {
            background-color: #dc3545;
        }

        /* Styles for the "Add More" button (the plus symbol) */
        .add-image-btn {
            background-color: #28a745; /* Green color */
            color: white;
            border: none;
            width: 40px; /* Make it a square */
            height: 40px; /* Make it a square */
            border-radius: 50%; /* Make it round */
            font-size: 1.8em; /* Large plus symbol */
            line-height: 1; /* Center the plus symbol vertically */
            cursor: pointer;
            display: flex; /* Use flex to center content */
            justify-content: center;
            align-items: center;
            transition: background-color 0.2s ease-in-out, transform 0.2s ease-in-out;
            margin-left: auto; /* Push it to the right */
            margin-top: 10px; /* Spacing from images */
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            display: none; /* Hidden by default */
        }
        .add-image-btn:hover {
            background-color: #218838;
            transform: scale(1.05);
        }

        .submit-button {
            background-color: #007bff;
            color: white;
            padding: 14px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.15em;
            width: 100%;
            margin-top: 25px;
            transition: background-color 0.2s ease-in-out, transform 0.2s ease-in-out;
        }
        .submit-button:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
        }
        .message {
            margin-bottom: 20px;
            padding: 12px;
            border-radius: 8px;
            text-align: center;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Add New Product</h1>
        <?php echo $message; // Display PHP messages here ?>
        <form action="index.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="productId">Product ID:</label>
                <input type="text" id="productId" name="productId" value="(Auto-Generated on Submit)" disabled>
            </div>

            <div class="form-group">
                <label for="productName">Product Name:</label>
                <input type="text" id="productName" name="productName" required pattern="^[a-zA-Z0-9\s]+$" title="Only letters, numbers, and spaces are allowed.">
            </div>

            <div class="form-group">
                <label for="price">Price (RS):</label>
                <input type="number" id="price" name="price" step="0.01" min="0" required>
            </div>

            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description" rows="5"></textarea>
            </div>

            <div class="form-group">
                <label for="productImages">Image Collection:</label>
                <input type="file" id="productImages" name="productImages[]" accept="image/*" multiple>
                <button type="button" class="custom-file-upload" id="chooseImageBtn">Choose Images</button>
                <div class="image-preview-container" id="imagePreviewContainer">
                    </div>
                <button type="button" class="add-image-btn" id="addImageBtn">+</button>
            </div>

            <button type="submit" class="submit-button">Submit</button>
        </form>
    </div>

    <script>
        const productImagesInput = document.getElementById('productImages');
        const imagePreviewContainer = document.getElementById('imagePreviewContainer');
        const chooseImageBtn = document.getElementById('chooseImageBtn');
        const addImageBtn = document.getElementById('addImageBtn');

        let selectedFiles = new DataTransfer(); // Use DataTransfer object to manage files

        const MAX_FILE_SIZE = 200 * 1024; // 200 KB in bytes

        // Function to update the hidden file input with current selectedFiles
        function updateFileInput() {
            productImagesInput.files = selectedFiles.files;
            // Show/hide the "+" button based on whether images are selected
            if (selectedFiles.files.length > 0) {
                addImageBtn.style.display = 'flex'; // Show the plus button
            } else {
                addImageBtn.style.display = 'none'; // Hide if no images
            }
        }

        // Function to display current image previews
        function displayPreviews() {
            imagePreviewContainer.innerHTML = ''; // Clear existing previews

            for (let i = 0; i < selectedFiles.files.length; i++) {
                const file = selectedFiles.files[i];

                // Client-side check for image size before creating preview
                if (file.size > MAX_FILE_SIZE) {
                    // Alert the user and do NOT create a preview for this file
                    alert(`Warning: "${file.name}" is too large (${(file.size / 1024).toFixed(2)} KB). Max allowed is 200 KB. This image will not be processed.`);
                    continue; // Skip this file and move to the next one
                }

                const reader = new FileReader();

                reader.onload = function(e) {
                    const previewDiv = document.createElement('div');
                    previewDiv.classList.add('image-preview');
                    previewDiv.innerHTML = `
                        <img src="${e.target.result}" alt="Image Preview">
                        <button type="button" class="remove-btn" data-index="${i}">x</button>
                    `;
                    imagePreviewContainer.appendChild(previewDiv);

                    // Add event listener for the remove button
                    previewDiv.querySelector('.remove-btn').addEventListener('click', function() {
                        const indexToRemove = parseInt(this.dataset.index);
                        removeFile(indexToRemove);
                    });
                };
                reader.readAsDataURL(file);
            }
            updateFileInput(); // Make sure the hidden input is updated
        }

        // Function to remove a file from the DataTransfer object
        function removeFile(index) {
            const newDT = new DataTransfer();
            for (let i = 0; i < selectedFiles.files.length; i++) {
                if (i !== index) {
                    newDT.items.add(selectedFiles.files[i]);
                }
            }
            selectedFiles = newDT;
            displayPreviews(); // Re-render previews
        }

        // Event listener for the initial "Choose Images" button
        chooseImageBtn.addEventListener('click', function() {
            productImagesInput.click(); // Trigger the hidden file input click
        });

        // Event listener for the "Add More" (plus symbol) button
        addImageBtn.addEventListener('click', function() {
            productImagesInput.click(); // Trigger the hidden file input click
        });


        // Event listener for when files are selected in the input field
        productImagesInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                const newFiles = Array.from(this.files); // Convert FileList to Array
                const filteredFiles = [];

                newFiles.forEach(file => {
                    if (file.size <= MAX_FILE_SIZE) {
                        filteredFiles.push(file);
                    } else {
                        // Alert for each oversized file at the moment of selection
                        alert(`"${file.name}" is too large (${(file.size / 1024).toFixed(2)} KB). Max allowed is 200 KB. This file will not be added.`);
                    }
                });

                // Add only valid (<= 200KB) files to the selectedFiles DataTransfer object
                filteredFiles.forEach(file => {
                    selectedFiles.items.add(file);
                });
                
                // Clear the original input's files after processing to avoid re-adding
                productImagesInput.value = ''; // This clears the FileList, crucial for consistent behavior

                displayPreviews(); // Update previews
            }
        });

        // Initial call to display previews in case of pre-filled input (unlikely on fresh load)
        if (productImagesInput.files.length > 0) {
            // Re-process initial files through the change listener logic to apply filters
            const initialFiles = Array.from(productImagesInput.files);
            productImagesInput.files = initialFiles; // Temporarily set back to trigger the listener
            productImagesInput.dispatchEvent(new Event('change')); // Manually trigger change event
        }
    </script>
</body>
</html>