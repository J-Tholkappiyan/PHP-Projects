<?php
session_start(); // Start session at the very top for message handling

// Error reporting for development (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$message = ''; // To display success or error messages

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- 1. Initialize Errors and Sanitize Inputs ---
    $errors = []; // This array will hold ALL errors that prevent product submission

    $productName = isset($_POST['product_name']) ? htmlspecialchars(trim($_POST['product_name'])) : '';
    $price = isset($_POST['price']) ? filter_var($_POST['price'], FILTER_VALIDATE_FLOAT) : null;
    $description = isset($_POST['description']) ? htmlspecialchars(trim($_POST['description'])) : '';

    if (empty($productName)) {
        $errors[] = "Product Name is required.";
    }
    if ($price === null || $price <= 0) {
        $errors[] = "Price must be a positive number.";
    }
    if (empty($description)) {
        $errors[] = "Description is required.";
    }

    // --- 2. Image Upload Handling (STRICT < 200KB Validation) ---
    $validImagesForProcessing = []; // Only images that pass ALL checks will be stored here temporarily
    $uploadImageDir = 'product_images/';
    $max_file_size_bytes = 200 * 1024; // 200 KB in bytes (204800 bytes)

    // Ensure the 'product_images' directory exists
    if (!is_dir($uploadImageDir)) {
        // Attempt to create it. If it fails, add a critical error.
        if (!mkdir($uploadImageDir, 0777, true)) {
            $errors[] = "Critical error: Product image directory '{$uploadImageDir}' could not be created. Check permissions.";
        }
    }

    $anyImageFileSelectedByUser = false; // Flag: Did the user select *any* file in *any* input field?

    if (isset($_FILES['product_images']) && is_array($_FILES['product_images']['name'])) {
        // For server-side, let's keep track of actual files being processed to avoid duplicates too
        // although client-side should largely prevent this.
        $processedFileIdentifiers = []; 

        foreach ($_FILES['product_images']['name'] as $key => $fileName) {
            // Check if this specific file input actually had a file selected (not empty string)
            if (!empty($fileName)) {
                $anyImageFileSelectedByUser = true; // Yes, user selected at least one file overall

                $fileTmpName = $_FILES['product_images']['tmp_name'][$key];
                $fileSize = $_FILES['product_images']['size'][$key];
                $fileError = $_FILES['product_images']['error'][$key];
                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                // Generate a unique identifier for this file based on its name and size
                // Note: Real-world applications might use file hashes (MD5/SHA1) for true uniqueness,
                // but name+size is generally sufficient for preventing accidental duplicates.
                $fileIdentifier = $fileName . '_' . $fileSize; 

                // --- Server-side Duplicate Check ---
                if (in_array($fileIdentifier, $processedFileIdentifiers)) {
                    $errors[] = "Duplicate file detected: '{$fileName}' was already selected. Please choose unique images.";
                    continue; 
                }

                // --- FIRST CHECK: PHP System-Level Upload Errors (CRITICAL) ---
                if ($fileError !== UPLOAD_ERR_OK) {
                    $errorMsg = "File '{$fileName}' could not be uploaded by the server: ";
                    switch ($fileError) {
                        case UPLOAD_ERR_INI_SIZE:
                            $errorMsg .= "It's too large. Check server's 'upload_max_filesize' and 'post_max_size' in php.ini. Currently: " . ini_get('upload_max_filesize') . " and " . ini_get('post_max_size') . ".";
                            break;
                        case UPLOAD_ERR_FORM_SIZE:
                            $errorMsg .= "It's too large, exceeds HTML form MAX_FILE_SIZE.";
                            break;
                        case UPLOAD_ERR_PARTIAL:
                            $errorMsg .= "Only partially uploaded. Please retry.";
                            break;
                        case UPLOAD_ERR_NO_TMP_DIR:
                            $errorMsg .= "Missing temporary server folder for uploads.";
                            break;
                        case UPLOAD_ERR_CANT_WRITE:
                            $errorMsg .= "Server failed to write the file to disk. Check server permissions.";
                            break;
                        case UPLOAD_ERR_EXTENSION:
                            $errorMsg .= "A PHP extension stopped the upload.";
                            break;
                        default:
                            $errorMsg .= "Unknown internal server error (code: {$fileError}).";
                            break;
                    }
                    $errors[] = $errorMsg; // Add this critical error
                    continue; // Skip all further checks for this problematic file
                }

                // --- SECOND CHECK: STRICT CUSTOM SIZE VALIDATION (< 200KB) ---
                // This check is only performed if PHP itself didn't stop the upload.
                if ($fileSize >= $max_file_size_bytes) {
                    $errors[] = "File '{$fileName}' is too large (".round($fileSize / 1024, 2)."KB). All images MUST be strictly less than 200KB.";
                    // We DO NOT store this file in $validImagesForProcessing, so it won't be moved.
                    continue; // Skip further checks for this file
                }

                // --- THIRD CHECK: Image Type Validation ---
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                if (!in_array($fileExt, $allowed_extensions)) {
                    $errors[] = "File '{$fileName}' has an invalid image type. Only JPG, JPEG, PNG, GIF are allowed.";
                    continue; // Skip this file
                }

                // If an image reaches here, it has passed ALL validation checks
                $validImagesForProcessing[] = [
                    'tmp_name' => $fileTmpName,
                    'new_name' => uniqid('img_', true) . '.' . $fileExt
                ];
                $processedFileIdentifiers[] = $fileIdentifier; // Mark this file as processed
            }
        }
    }

    // --- Final Check: Is at least one valid image required and provided? ---
    if (!$anyImageFileSelectedByUser || empty($validImagesForProcessing)) {
        if ($anyImageFileSelectedByUser && empty($validImagesForProcessing) && empty($errors)) {
            // This path means user selected files, but ALL of them failed custom validation (size/type)
            // AND no PHP system-level errors were encountered. This is an important distinction.
            $errors[] = "No valid product images could be processed. Please ensure images are strictly less than 200KB and are valid types.";
        } elseif (!$anyImageFileSelectedByUser) {
            // This path means the user didn't even select any files.
            $errors[] = "At least one product image is required.";
        }
    }


    // --- 3. If NO errors after ALL input and image validation, proceed to save product ---
    if (empty($errors)) {
        // --- Move uploaded files only now that ALL validation has passed ---
        $finalImageNames = [];
        foreach ($validImagesForProcessing as $img) {
            $targetFile = $uploadImageDir . $img['new_name'];
            if (move_uploaded_file($img['tmp_name'], $targetFile)) {
                $finalImageNames[] = $img['new_name'];
            } else {
                // This indicates a very serious server-side issue during file move
                // (e.g., permissions changed, disk full after validation).
                // Add a critical error and clean up any previously moved files for this product.
                $errors[] = "CRITICAL: Failed to save image '{$img['new_name']}' to final directory. Product not added due to server error.";
                // Attempt to clean up any files that were already moved for this product
                foreach($finalImageNames as $movedImg) {
                    if (file_exists($uploadImageDir . $movedImg)) {
                        unlink($uploadImageDir . $movedImg);
                    }
                }
                break; // Stop processing and fall to error display
            }
        }

        // Re-check errors array, in case a critical error occurred during `move_uploaded_file`
        if (!empty($errors)) {
             // If errors occurred during moving, set session message and redirect to show error
             $_SESSION['form_message'] = '<div class="message error">' . implode('<br>', $errors) . '</div>';
             header('Location: add_products.php');
             exit();
        }

        // Generate a unique ID for the product
        $productId = uniqid('prod_');

        // Prepare product data with successfully moved images
        $newProduct = [
            'id' => $productId,
            'product_name' => $productName,
            'price' => $price,
            'description' => $description,
            'images' => $finalImageNames, // These are the names of the successfully moved files
        ];

        // --- 4. Save to products.json ---
        $productsFilePath = 'products.json';
        $products = [];
        if (file_exists($productsFilePath)) {
            $jsonData = file_get_contents($productsFilePath);
            $products = json_decode($jsonData, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("Corrupt products.json file found. Resetting products array for new data.");
                $products = []; // If JSON is corrupt, start fresh to prevent further issues
            }
        }

        $products[] = $newProduct;
        file_put_contents($productsFilePath, json_encode($products, JSON_PRETTY_PRINT));

        // --- 5. Generate Product HTML File ---
        $productHtmlDir = 'products/';
        if (!is_dir($productHtmlDir)) {
            mkdir($productHtmlDir, 0777, true);
        }

        $productHtmlFileName = $productHtmlDir . $productId . '.html';
        ob_start(); // Start output buffering for HTML generation
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo htmlspecialchars($newProduct['product_name']); ?></title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f4f4; color: #333; }
                .container { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); max-width: 800px; margin: auto; }
                h1 { color: #0056b3; }
                .price { font-size: 1.5em; color: #e67e22; margin-bottom: 15px; }
                .description { margin-bottom: 20px; line-height: 1.6; }
                .image-gallery { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 20px; }
                .image-gallery img { max-width: 150px; height: auto; border: 1px solid #ddd; border-radius: 4px; }
                .back-link { display: block; margin-top: 20px; text-decoration: none; color: #007bff; }
            </style>
        </head>
        <body>
            <div class="container">
                <h1><?php echo htmlspecialchars($newProduct['product_name']); ?></h1>
                <p><strong>Product ID:</strong> <?php echo htmlspecialchars($newProduct['id']); ?></p>
                <p class="price">Price: ₹<?php echo number_format($newProduct['price'], 2); ?></p>
                <h3>Description:</h3>
                <p class="description"><?php echo nl2br(htmlspecialchars($newProduct['description'])); ?></p>

                <h3>Images:</h3>
                <div class="image-gallery">
                    <?php if (!empty($newProduct['images'])): ?>
                        <?php foreach ($newProduct['images'] as $image): ?>
                            <img src="../product_images/<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($newProduct['product_name']); ?>">
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No images available.</p>
                    <?php endif; ?>
                </div>
                <a href="../employee.php" class="back-link">Back to Dashboard</a>
            </div>
        </body>
        </html>
        <?php
        $productHtmlContent = ob_get_clean();
        file_put_contents($productHtmlFileName, $productHtmlContent);

        // In add_products.php
// Success message and redirect to the newly created product's HTML page
$_SESSION['form_message'] = '<div class="message success">Product added successfully! You are now viewing the product details.</div>';
header('Location: ' . $productHtmlFileName); // Redirect to the newly created product's HTML page
exit();

    } else {
        // If there are ANY errors, display them on the current page (no redirect if main validation fails)
        $message = '<div class="message error">' . implode('<br>', $errors) . '</div>';
    }
}

// Display status message if redirected (using session for all messages)
if (isset($_SESSION['form_message'])) {
    $message = $_SESSION['form_message'];
    unset($_SESSION['form_message']); // Clear the message after displaying
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Product</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 600px;
            box-sizing: border-box;
        }
        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 25px;
        }
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group textarea {
            width: calc(100% - 20px);
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
            box-sizing: border-box;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        .form-group input[type="file"] {
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 5px;
            width: calc(100% - 12px);
        }
        .image-input-group {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .image-input-group input[type="file"] {
            flex-grow: 1;
            margin-right: 10px;
        }
        .add-image-btn {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.2em;
            line-height: 1;
            transition: background-color 0.3s ease;
        }
        .add-image-btn:hover {
            background-color: #218838;
        }
        .remove-image-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 10px 12px;
            border-radius: 5px;
            cursor: pointer;
            margin-left: 5px;
            font-size: 0.9em;
            transition: background-color 0.3s ease;
        }
        .remove-image-btn:hover {
            background-color: #c82333;
        }
        .submit-btn {
            background-color: #007bff;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1em;
            width: 100%;
            transition: background-color 0.3s ease;
            margin-top: 20px;
        }
        .submit-btn:hover {
            background-color: #0056b3;
        }
        .message {
            margin-bottom: 20px;
            text-align: center;
            padding: 10px;
            border-radius: 5px;
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
        .back-to-dashboard-btn {
            display: block;
            width: 100%;
            padding: 10px 15px;
            margin-top: 15px;
            background-color: #6c757d;
            color: white;
            border: none;
            border-radius: 5px;
            text-align: center;
            text-decoration: none;
            font-size: 1em;
            transition: background-color 0.3s ease;
            box-sizing: border-box;
        }
        .back-to-dashboard-btn:hover {
            background-color: #5a6268;
        }
        .file-size-info {
            font-size: 0.8em;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Add New Product</h2>
        <?php
        if (!empty($message)) {
            echo $message;
        }
        ?>

        <form action="add_products.php" method="POST" enctype="multipart/form-data" id="product-form">
            <div class="form-group">
                <label for="product_name">Product Name:</label>
                <input type="text" id="product_name" name="product_name" required value="<?php echo isset($_POST['product_name']) ? htmlspecialchars($_POST['product_name']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="price">Price (₹):</label>
                <input type="number" id="price" name="price" step="0.01" min="0" required value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
            </div>

            <div class="form-group">
                <label>Product Images (Strictly Less Than 200KB each):</label>
                <div id="image-inputs-container">
                    <div class="image-input-group">
                        <input type="file" name="product_images[]" accept="image/*" multiple>
                        <button type="button" class="add-image-btn" id="add-image-btn">+</button>
                    </div>
                </div>
                <p class="file-size-info">Maximum file size: Strictly less than 200KB per image. Allowed types: JPG, JPEG, PNG, GIF</p>
            </div>

            <button type="submit" class="submit-btn">Add Product</button>
            <a href="employee_login.php" class="back-to-dashboard-btn">Back to Employee Dashboard</a>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const addImageBtn = document.getElementById('add-image-btn');
            const imageInputsContainer = document.getElementById('image-inputs-container');
            const productForm = document.getElementById('product-form');
            const maxSizeBytes = 200 * 1024; // 200KB in bytes (204800 bytes)

            // Keep track of file identifiers (name + size) that are currently selected in the form
            // This is for client-side duplicate checking
            let selectedFileIdentifiers = new Set(); 

            // Function to get a unique identifier for a file
            function getFileIdentifier(file) {
                // Use file.name and file.size for a simple identifier.
                // For very robust checking (e.g., if different files have same name/size),
                // you might generate a hash, but that's more complex client-side.
                return file.name + '_' + file.size;
            }

            // Function to attach validation to a file input
            function attachFileInputValidation(inputElement) {
                inputElement.addEventListener('change', function() {
                    let errorFoundForThisInput = false;
                    const newlySelectedFiles = Array.from(this.files); // Convert FileList to Array

                    // Clear this input's old files from the global set before adding new ones
                    // This handles cases where a user changes their mind and picks different files
                    // for an already used input field.
                    // Loop through previous files and remove their identifiers
                    const oldFiles = this.oldFiles || []; // Use a custom property to store previously selected files
                    oldFiles.forEach(file => {
                        selectedFileIdentifiers.delete(getFileIdentifier(file));
                    });
                    
                    // Reset oldFiles for this input
                    this.oldFiles = [];


                    if (newlySelectedFiles.length > 0) {
                        for (let i = 0; i < newlySelectedFiles.length; i++) {
                            const file = newlySelectedFiles[i];
                            if (file) {
                                // 1. Check for size
                                if (file.size >= maxSizeBytes) { // STRICTLY >= 200KB
                                    alert(`Client-Side Alert: File "${file.name}" is too large (${(file.size / 1024).toFixed(2)}KB). Images must be strictly less than 200KB. Please remove this image.`);
                                    this.value = ''; // Clear the problematic file input
                                    errorFoundForThisInput = true;
                                    break; // Stop checking files for this specific input
                                }

                                // 2. Check for duplicates against already selected files in other inputs
                                const fileIdentifier = getFileIdentifier(file);
                                if (selectedFileIdentifiers.has(fileIdentifier)) {
                                    alert(`Client-Side Alert: File "${file.name}" has already been selected. Please choose a unique image.`);
                                    this.value = ''; // Clear the problematic file input
                                    errorFoundForThisInput = true;
                                    break; // Stop checking files for this specific input
                                }

                                // If no size or duplicate errors, add to the set and to oldFiles
                                selectedFileIdentifiers.add(fileIdentifier);
                                this.oldFiles.push(file); // Store files for future comparison
                            }
                        }
                    } else {
                        // If no files are selected (input cleared), ensure old files are removed from the set
                        oldFiles.forEach(file => {
                            selectedFileIdentifiers.delete(getFileIdentifier(file));
                        });
                    }

                    // After all checks, if an error was found for this input, clear it again
                    // This is redundant with the breaks above, but ensures consistent state.
                    if (errorFoundForThisInput) {
                        this.value = '';
                        // Also remove any files that were *just* added to selectedFileIdentifiers from this input
                        // before an error was found. Rebuild the set for accuracy.
                        selectedFileIdentifiers = new Set();
                        document.querySelectorAll('input[type="file"][name="product_images[]"]').forEach(input => {
                            if (input !== this && input.files && input.files.length > 0) {
                                Array.from(input.files).forEach(f => selectedFileIdentifiers.add(getFileIdentifier(f)));
                            }
                        });
                    }
                });
            }

            // Attach validation to the initial file input field
            const initialFileInput = document.querySelector('input[type="file"][name="product_images[]"]');
            if (initialFileInput) {
                attachFileInputValidation(initialFileInput);
            }

            // Add new image input field
            addImageBtn.addEventListener('click', function() {
                const newImageInputGroup = document.createElement('div');
                newImageInputGroup.classList.add('image-input-group');

                const newImageInput = document.createElement('input');
                newImageInput.type = 'file';
                newImageInput.name = 'product_images[]';
                newImageInput.accept = 'image/*';
                newImageInput.multiple = true; 
                
                // Attach validation to the newly created file input
                attachFileInputValidation(newImageInput);

                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.textContent = 'X';
                removeBtn.classList.add('remove-image-btn');
                removeBtn.onclick = function() {
                    // When an input is removed, also remove its associated files from the set
                    const filesToRemove = this.previousElementSibling.oldFiles || [];
                    filesToRemove.forEach(file => {
                        selectedFileIdentifiers.delete(getFileIdentifier(file));
                    });
                    newImageInputGroup.remove();
                };

                newImageInputGroup.appendChild(newImageInput);
                newImageInputGroup.appendChild(removeBtn);
                imageInputsContainer.appendChild(newImageInputGroup);
            });

            // Price input validation (client-side)
            const priceInput = document.getElementById('price');
            if (priceInput) {
                priceInput.addEventListener('input', function() {
                    this.value = this.value.replace(/[^0-9.]/g, '');
                    const parts = this.value.split('.');
                    if (parts.length > 2) {
                        this.value = parts[0] + '.' + parts.slice(1).join('');
                    }
                });
            }

            // --- Client-Side Form Submission Validation (as a final safeguard) ---
            productForm.addEventListener('submit', function(e) {
                const fileInputs = document.querySelectorAll('input[type="file"][name="product_images[]"]');
                let anyFileSelectedClientSide = false;
                let clientSideErrorFoundOnSubmit = false; 

                // Rebuild selectedFileIdentifiers from scratch for the final check to ensure accuracy
                selectedFileIdentifiers = new Set();

                fileInputs.forEach(input => {
                    if (input.files && input.files.length > 0) {
                        anyFileSelectedClientSide = true; 
                        for (let i = 0; i < input.files.length; i++) {
                            const file = input.files[i];
                            if (file) {
                                // Double-check size
                                if (file.size >= maxSizeBytes) { 
                                    alert(`Client-Side Alert on Submit: File "${file.name}" is still too large (${(file.size / 1024).toFixed(2)}KB). Images must be strictly less than 200KB. Please remove this image.`);
                                    clientSideErrorFoundOnSubmit = true;
                                    e.preventDefault(); 
                                    break; 
                                }
                                // Double-check for duplicates on submit (unlikely if change listener works, but good safeguard)
                                const fileIdentifier = getFileIdentifier(file);
                                if (selectedFileIdentifiers.has(fileIdentifier)) {
                                    alert(`Client-Side Alert on Submit: File "${file.name}" is a duplicate. Please choose unique images.`);
                                    clientSideErrorFoundOnSubmit = true;
                                    e.preventDefault(); 
                                    break;
                                }
                                selectedFileIdentifiers.add(fileIdentifier);
                            }
                        }
                    }
                    if (clientSideErrorFoundOnSubmit) {
                        return; 
                    }
                });

                if (!anyFileSelectedClientSide && !clientSideErrorFoundOnSubmit) {
                    alert("Client-Side Alert: At least one product image is required.");
                    e.preventDefault(); 
                } else if (clientSideErrorFoundOnSubmit) {
                    console.log("Client-side validation prevented form submission due to file errors on final check.");
                }
            });
        });
    </script>
</body>
</html>