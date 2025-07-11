<?php
// Error reporting for development (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

// Ensure employee is logged in
if (!isset($_SESSION['employee_logged_in']) || $_SESSION['employee_logged_in'] !== true) {
    header('Location: employee.php');
    exit();
}

$productId = isset($_GET['id']) ? htmlspecialchars($_GET['id']) : null;
$product = null;
$message = '';
$productsFilePath = 'products.json';

// Load existing product data for pre-filling the form
if ($productId) {
    if (file_exists($productsFilePath)) {
        $jsonData = file_get_contents($productsFilePath);
        $products = json_decode($jsonData, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            foreach ($products as $p) {
                if ($p['id'] === $productId) {
                    $product = $p;
                    break;
                }
            }
        }
    }

    if (!$product) {
        $message = '<div class="message error">Product not found.</div>';
    }
} else {
    $message = '<div class="message error">No product ID specified for editing.</div>';
}

// --- Handle Form Submission for Update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    if (!$product) {
        $message = '<div class="message error">Cannot update: Product data not loaded.</div>';
    } else {
        $newProductName = isset($_POST['product_name']) ? htmlspecialchars(trim($_POST['product_name'])) : '';
        $newPrice = isset($_POST['price']) ? filter_var($_POST['price'], FILTER_VALIDATE_FLOAT) : null;
        $newDescription = isset($_POST['description']) ? htmlspecialchars(trim($_POST['description'])) : '';

        $existingImagePayloads = isset($_POST['existing_images']) ? $_POST['existing_images'] : [];
        $errors = [];

        if (empty($newProductName)) {
            $errors[] = "Product Name is required.";
        }
        if ($newPrice === null || $newPrice <= 0) {
            $errors[] = "Price must be a positive number.";
        }
        if (empty($newDescription)) {
            $errors[] = "Description is required.";
        }

        // Initialize with existing images that are kept (parsed from JSON payload)
        $currentImagesData = [];
        $existingOriginalNamesInForm = [];
        $existingUniqueIdsToKeep = [];
        $existingFileHashes = [];

        foreach ($existingImagePayloads as $payload) {
            $imageData = json_decode(htmlspecialchars_decode($payload), true);
            if ($imageData && isset($imageData['unique_id']) && !empty($imageData['unique_id'])) {
                $currentImagesData[] = $imageData;
                $existingUniqueIdsToKeep[] = $imageData['unique_id'];
                if (isset($imageData['original_name'])) {
                    $existingOriginalNamesInForm[] = $imageData['original_name'];
                }
                if (isset($imageData['file_hash']) && file_exists('product_images/' . $imageData['unique_id'])) {
                    $existingFileHashes[] = $imageData['file_hash'];
                }
            }
        }

        // Handle new image uploads
        if (isset($_FILES['product_images'])) {
            $totalFiles = count($_FILES['product_images']['name']);
            for ($i = 0; $i < $totalFiles; $i++) {
                $fileName = $_FILES['product_images']['name'][$i];
                $fileTmpName = $_FILES['product_images']['tmp_name'][$i];
                $fileSize = $_FILES['product_images']['size'][$i];
                $fileError = $_FILES['product_images']['error'][$i];
                $fileType = $_FILES['product_images']['type'][$i];

                if ($fileError === UPLOAD_ERR_OK && !empty($fileName)) {
                    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    $allowed = ['jpg', 'jpeg', 'png', 'gif'];

                    if (in_array($fileExt, $allowed)) {
                        // Server-side check for 200KB limit
                        if ($fileSize <= 204800) {
                            // Server-side check for 5MB limit
                            if ($fileSize < 5000000) {
                                // Generate file hash for duplicate content detection
                                $fileHash = md5_file($fileTmpName);
                                
                                // Server-side duplicate check against existing images
                                $allExistingHashes = array_merge(
                                    $existingFileHashes,
                                    array_column($currentImagesData, 'file_hash')
                                );
                                
                                $allExistingNames = array_merge(
                                    $existingOriginalNamesInForm,
                                    array_column($currentImagesData, 'original_name')
                                );

                                // Check both filename and content hash
                                if (in_array(strtolower($fileName), array_map('strtolower', $allExistingNames))) {
                                    $errors[] = "Filename '$fileName' already exists for this product.";
                                    continue;
                                }
                                
                                if (in_array($fileHash, $allExistingHashes)) {
                                    $errors[] = "This exact image already exists for this product.";
                                    continue;
                                }

                                $newUniqueFileName = uniqid('', true) . '.' . $fileExt;
                                $fileDestination = 'product_images/' . $newUniqueFileName;

                                if (move_uploaded_file($fileTmpName, $fileDestination)) {
                                    $currentImagesData[] = [
                                        'unique_id' => $newUniqueFileName,
                                        'original_name' => $fileName,
                                        'file_hash' => $fileHash
                                    ];
                                    $existingOriginalNamesInForm[] = $fileName;
                                    $existingUniqueIdsToKeep[] = $newUniqueFileName;
                                    $existingFileHashes[] = $fileHash;
                                } else {
                                    $errors[] = "Failed to upload new image: $fileName";
                                }
                            } else {
                                $errors[] = "New file '$fileName' is too large (over 5MB).";
                            }
                        } else {
                            $errors[] = "New file '$fileName' must be less than 200KB.";
                        }
                    } else {
                        $errors[] = "Invalid file type for new image '$fileName'.";
                    }
                } else if ($fileError !== UPLOAD_ERR_NO_FILE) {
                    $errors[] = "Error uploading new image '$fileName'. Code: " . $fileError;
                }
            }
        }

        // If no errors, proceed with update
        if (empty($errors)) {
            $allProducts = [];
            if (file_exists($productsFilePath)) {
                $jsonData = file_get_contents($productsFilePath);
                $allProducts = json_decode($jsonData, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $allProducts = [];
                }
            }

            $oldImagesToDelete = [];
            foreach ($allProducts as &$p) {
                if ($p['id'] === $productId) {
                    // Collect unique IDs of images currently stored for this product
                    $currentProductUniqueIds = [];
                    if (isset($p['images']) && is_array($p['images'])) {
                        foreach ($p['images'] as $img) {
                            if (is_string($img)) {
                                $currentProductUniqueIds[] = $img;
                            } elseif (is_array($img) && isset($img['unique_id'])) {
                                $currentProductUniqueIds[] = $img['unique_id'];
                            }
                        }
                    }

                    // Determine which old unique IDs are no longer in existingUniqueIdsToKeep
                    $oldImagesToDelete = array_diff($currentProductUniqueIds, $existingUniqueIdsToKeep);

                    $p['product_name'] = $newProductName;
                    $p['price'] = $newPrice;
                    $p['description'] = $newDescription;
                    $p['images'] = $currentImagesData;
                    $product = $p;
                }
            }
            unset($p);

            file_put_contents($productsFilePath, json_encode($allProducts, JSON_PRETTY_PRINT));

            foreach ($oldImagesToDelete as $imgToDelete) {
                $imagePath = 'product_images/' . $imgToDelete;
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            // --- Regenerate Product HTML File ---
            $productHtmlFileName = 'products/' . $productId . '.html';
            ob_start();
            ?>
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title><?php echo htmlspecialchars($product['product_name']); ?></title>
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
                    <h1><?php echo htmlspecialchars($product['product_name']); ?></h1>
                    <p><strong>Product ID:</strong> <?php echo htmlspecialchars($product['id']); ?></p>
                    <p class="price">Price: ₹<?php echo number_format($product['price'], 2); ?></p>
                    <h3>Description:</h3>
                    <p class="description"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>

                    <h3>Images:</h3>
                    <div class="image-gallery">
                        <?php if (!empty($product['images'])): ?>
                            <?php foreach ($product['images'] as $image):
                                $displayUniqueId = is_array($image) ? $image['unique_id'] : $image;
                            ?>
                                <img src="../product_images/<?php echo htmlspecialchars($displayUniqueId); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
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

            $message = '<div class="message success">Product updated successfully!</div>';
            header('Location: employee.php?status=updated');
            exit();
        } else {
            $message = '<div class="message error">' . implode('<br>', $errors) . '</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - <?php echo $product ? htmlspecialchars($product['product_name']) : 'Not Found'; ?></title>
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
        .image-preview-group {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }
        .image-preview-item {
            position: relative;
            border: 1px solid #ddd;
            padding: 5px;
            border-radius: 5px;
        }
        .image-preview-item img {
            max-width: 100px;
            height: auto;
            display: block;
        }
        .image-preview-item .remove-img-btn {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            line-height: 1;
            font-size: 1.1em;
            cursor: pointer;
            text-align: center;
        }
        .image-input-group {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .image-input-group input[type="file"] {
            flex-grow: 1;
            margin-right: 10px;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 5px;
            width: calc(100% - 12px);
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
        }
        .submit-btn:hover {
            background-color: #0056b3;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #007bff;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .duplicate-file {
            border: 2px solid #dc3545 !important;
            box-shadow: 0 0 5px rgba(220, 53, 69, 0.5);
        }
        .file-error {
            color: #dc3545;
            font-size: 0.8em;
            margin-top: 5px;
        }
    </style>
</head>
<body>
     <div class="container">
        <h2>Edit Product</h2>
        <?php echo $message; ?>

        <?php if ($product): ?>
            <form action="edit_product.php?id=<?php echo htmlspecialchars($productId); ?>" method="POST" enctype="multipart/form-data" id="edit-product-form">
                <input type="hidden" name="update_product" value="1">
                <div class="form-group">
                    <label for="product_name">Product Name:</label>
                    <input type="text" id="product_name" name="product_name" required value="<?php echo htmlspecialchars($product['product_name']); ?>">
                </div>

                <div class="form-group">
                    <label for="price">Price (₹):</label>
                    <input type="number" id="price" name="price" step="0.01" min="0" required value="<?php echo htmlspecialchars($product['price']); ?>">
                </div>

                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea id="description" name="description" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                </div>

                <div class="form-group">
                    <label>Current Images:</label>
                    <div class="image-preview-group" id="current-image-previews">
                        <?php
                        $existingProductOriginalNames = [];
                        $existingProductFileHashes = [];
                        if (!empty($product['images'])):
                            foreach ($product['images'] as $image_data):
                                $unique_id = '';
                                $original_name = '';
                                $file_hash = '';

                                if (is_string($image_data)) {
                                    $unique_id = $image_data;
                                    $original_name = $image_data;
                                    if (file_exists('product_images/' . $unique_id)) {
                                        $file_hash = md5_file('product_images/' . $unique_id);
                                    }
                                } elseif (is_array($image_data) && isset($image_data['unique_id'])) {
                                    $unique_id = $image_data['unique_id'];
                                    $original_name = isset($image_data['original_name']) ? $image_data['original_name'] : $unique_id;
                                    $file_hash = isset($image_data['file_hash']) ? $image_data['file_hash'] : '';
                                    if (empty($file_hash) && file_exists('product_images/' . $unique_id)) {
                                        $file_hash = md5_file('product_images/' . $unique_id);
                                    }
                                }

                                if (!empty($unique_id)) {
                                    $existingProductOriginalNames[] = htmlspecialchars($original_name);
                                    if (!empty($file_hash)) {
                                        $existingProductFileHashes[] = $file_hash;
                                    }
                        ?>
                                <div class="image-preview-item">
                                    <img src="product_images/<?php echo htmlspecialchars($unique_id); ?>" alt="Product Image">
                                    <button type="button" class="remove-img-btn"
                                            data-unique-id="<?php echo htmlspecialchars($unique_id); ?>"
                                            data-original-name="<?php echo htmlspecialchars($original_name); ?>"
                                            data-file-hash="<?php echo htmlspecialchars($file_hash); ?>">&times;</button>
                                    <input type="hidden" name="existing_images[]"
                                           value='<?php echo htmlspecialchars(json_encode([
                                               'unique_id' => $unique_id,
                                               'original_name' => $original_name,
                                               'file_hash' => $file_hash
                                           ]), ENT_QUOTES, 'UTF-8'); ?>'>
                                </div>
                            <?php
                                }
                            endforeach;
                        else: ?>
                            <p>No images currently.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label>Add More Images:</label>
                    <div id="image-inputs-container">
                        <div class="image-input-group">
                            <input type="file" name="product_images[]" accept="image/*" class="new-product-image-input" multiple>
                        </div>
                    </div>
                    <p class="help-text" style="font-size: 0.8em; color: #666;">Note: You can select multiple images at once. Duplicate images (by filename or content) will be rejected. Max size: 200KB per image</p>
                </div>

                <button type="submit" class="submit-btn">Update Product</button>
            </form>
            <a href="employee.php" class="back-link">Back to Employee Dashboard</a>
        <?php else: ?>
            <p>Product data could not be loaded. Please go back to the dashboard.</p>
            <a href="employee.php" class="back-link">Back to Employee Dashboard</a>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const imageInputsContainer = document.getElementById('image-inputs-container');
            const currentImagePreviews = document.getElementById('current-image-previews');
            const priceInput = document.getElementById('price');
            const form = document.getElementById('edit-product-form');
            const MAX_FILE_SIZE_KB = 200;
            const MAX_FILE_SIZE_BYTES = MAX_FILE_SIZE_KB * 1024;

            // Track existing and new filenames/hashes
            const existingProductOriginalNames = <?php echo json_encode($existingProductOriginalNames); ?>;
            const existingProductFileHashes = <?php echo json_encode($existingProductFileHashes); ?>;
            const newUploadedFiles = []; // Will store {name, hash, file} objects
            
            // Store all file inputs for duplicate checking
            const fileInputs = Array.from(document.querySelectorAll('.new-product-image-input'));

            // Enhanced file input change handler for multiple files
            function handleFileInputChange(event) {
                const fileInput = event.target;
                const fileGroup = fileInput.closest('.image-input-group');
                let errorElement = fileGroup.querySelector('.file-error');
                
                // Remove previous error messages and styling
                if (errorElement) {
                    errorElement.remove();
                }
                fileInput.classList.remove('duplicate-file');
                
                if (fileInput.files.length > 0) {
                    // Create error element if it doesn't exist
                    if (!errorElement) {
                        errorElement = document.createElement('div');
                        errorElement.className = 'file-error';
                        fileGroup.appendChild(errorElement);
                    }
                    
                    const errors = [];
                    const validFiles = [];
                    const processedFiles = [];
                    
                    // Process each selected file
                    Array.from(fileInput.files).forEach((file, index) => {
                        const originalFileName = file.name;
                        const fileSize = file.size;
                        
                        // Check file size
                        if (fileSize > MAX_FILE_SIZE_BYTES) {
                            errors.push(`File "${originalFileName}" is too large (max ${MAX_FILE_SIZE_KB}KB)`);
                            return;
                        }
                        
                        // Check against all known filenames (case insensitive)
                        const allExistingNames = [
                            ...existingProductOriginalNames.map(name => name.toLowerCase()),
                            ...newUploadedFiles.map(f => f.name.toLowerCase())
                        ];
                        
                        if (allExistingNames.includes(originalFileName.toLowerCase())) {
                            errors.push(`File "${originalFileName}" already exists`);
                            return;
                        }
                        
                        // Read file for content hash
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const fileContent = e.target.result;
                            const fileSignature = fileContent.substring(0, 1000);
                            
                            // Check against existing product hashes
                            if (existingProductFileHashes.some(hash => hash === fileSignature)) {
                                errors.push(`"${originalFileName}" is a duplicate of an existing product image`);
                                return;
                            }
                            
                            // Check against other selected files in this session
                            const duplicateFile = newUploadedFiles.find(f => f.signature === fileSignature);
                            if (duplicateFile) {
                                errors.push(`"${originalFileName}" is a duplicate of "${duplicateFile.name}"`);
                                return;
                            }
                            
                            // Check against other files in this batch
                            const duplicateInBatch = processedFiles.find(f => f.signature === fileSignature);
                            if (duplicateInBatch) {
                                errors.push(`"${originalFileName}" is a duplicate of "${duplicateInBatch.name}" in this upload`);
                                return;
                            }
                            
                            // If all checks pass, store the file info
                            const fileInfo = {
                                name: originalFileName,
                                signature: fileSignature,
                                file: file
                            };
                            
                            validFiles.push(fileInfo);
                            processedFiles.push(fileInfo);
                            
                            // Update the tracking arrays when all files are processed
                            if (index === fileInput.files.length - 1) {
                                if (errors.length > 0) {
                                    showFileError(fileGroup, errors.join('<br>'));
                                    fileInput.classList.add('duplicate-file');
                                } else {
                                    validFiles.forEach(fileInfo => {
                                        newUploadedFiles.push(fileInfo);
                                    });
                                }
                            }
                        };
                        reader.readAsDataURL(file);
                    });
                }
            }
            
            function showFileError(fileGroup, message) {
                let errorElement = fileGroup.querySelector('.file-error');
                if (!errorElement) {
                    errorElement = document.createElement('div');
                    errorElement.className = 'file-error';
                    fileGroup.appendChild(errorElement);
                }
                errorElement.innerHTML = message;
            }

            // Initialize file inputs
            fileInputs.forEach(input => {
                input.addEventListener('change', handleFileInputChange);
            });

            // Remove existing image previews
            if (currentImagePreviews) {
                currentImagePreviews.addEventListener('click', function(event) {
                    if (event.target.classList.contains('remove-img-btn')) {
                        const button = event.target;
                        const imageItem = button.closest('.image-preview-item');
                        const hiddenInput = imageItem.querySelector('input[name="existing_images[]"]');
                        const originalNameToRemove = button.dataset.originalName;
                        const fileHashToRemove = button.dataset.fileHash;

                        if (confirm('Are you sure you want to remove this image?')) {
                            // Remove from tracking arrays
                            const nameIndex = existingProductOriginalNames.indexOf(originalNameToRemove);
                            if (nameIndex > -1) {
                                existingProductOriginalNames.splice(nameIndex, 1);
                            }
                            
                            const hashIndex = existingProductFileHashes.indexOf(fileHashToRemove);
                            if (hashIndex > -1) {
                                existingProductFileHashes.splice(hashIndex, 1);
                            }
                            
                            if (hiddenInput) {
                                hiddenInput.value = '';
                            }
                            imageItem.remove();
                        }
                    }
                });
            }

            // Price validation
            if (priceInput) {
                priceInput.addEventListener('input', function() {
                    this.value = this.value.replace(/[^0-9.]/g, '');
                    const parts = this.value.split('.');
                    if (parts.length > 2) {
                        this.value = parts[0] + '.' + parts.slice(1).join('');
                    }
                });
            }
            
            // Form submission validation
            if (form) {
                form.addEventListener('submit', function(e) {
                    // Check for any duplicate files that might have slipped through
                    const allFiles = Array.from(document.querySelectorAll('.new-product-image-input'))
                        .flatMap(input => Array.from(input.files));
                    
                    const fileNames = allFiles.map(file => file.name.toLowerCase());
                    const uniqueNames = [...new Set(fileNames)];
                    
                    if (fileNames.length !== uniqueNames.length) {
                        alert('Duplicate files detected. Please remove them before submitting.');
                        e.preventDefault();
                        return;
                    }
                });
            }
        });
    </script>
</body>
</html>