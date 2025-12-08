<?php
require_once __DIR__ . '/../../models/auth.php';
require_once __DIR__ . '/../../models/rbac.php';
require_once __DIR__ . '/../../app/config/config.php';

require_login();
require_role('Customer');

$pharmacy_base_url = str_replace('/public', '', $config['base_url']);
error_log("DEBUG: Upload Page - $pharmacy_base_url: " . $pharmacy_base_url);

$uploadMessage = '';
$analysisResult = '';
$uploadedFileName = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['prescriptionFile'])) {
        $targetDir = __DIR__ . '/../../uploads/prescriptions/';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $fileName = basename($_FILES['prescriptionFile']['name']);
        $targetFilePath = $targetDir . $fileName;
        $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);

        // Allow certain file formats
        $allowTypes = array('jpg', 'png', 'jpeg', 'pdf');
        if (in_array($fileType, $allowTypes)) {
            // Upload file to server
            if (move_uploaded_file($_FILES['prescriptionFile']['tmp_name'], $targetFilePath)) {
                $uploadMessage = "The file ". htmlspecialchars($fileName). " has been uploaded successfully.";
                $uploadedFileName = $fileName;
                
                // Placeholder for prescription analysis
                // Simulate analysis result - this is where real OCR would go
                $analysisResult = "[Placeholder Analysis for {$fileName}: Paracetamol 500mg (x2), Amoxicillin 250mg (x1)]";

            } else {
                $uploadMessage = "Error uploading your file.";
            }
        } else {
            $uploadMessage = "Sorry, only JPG, JPEG, PNG, & PDF files are allowed to upload.";
        }
    } else if (isset($_POST['confirmCorrect']) && $_POST['confirmCorrect'] === 'yes' && isset($_POST['analysisResultText'])) {
        // User confirmed analysis, now add to cart
        $confirmedAnalysisText = $_POST['analysisResultText'];
        $prescriptionItems = [];

        // Simulate parsing the analysis text into cart-compatible items
        // In a real scenario, this would come from the actual OCR output
        if (strpos($confirmedAnalysisText, 'Paracetamol') !== false) {
            $prescriptionItems[] = ['id' => 'presc_1', 'name' => 'Paracetamol 500mg', 'price' => 5.00, 'quantity' => 2];
        }
        if (strpos($confirmedAnalysisText, 'Amoxicillin') !== false) {
            $prescriptionItems[] = ['id' => 'presc_2', 'name' => 'Amoxicillin 250mg', 'price' => 12.50, 'quantity' => 1];
        }

        if (!empty($prescriptionItems)) {
            // Prepare items for localStorage
            $cartUpdate = [];
            foreach ($prescriptionItems as $item) {
                $cartUpdate[$item['id']] = $item;
            }
            $cartDataJson = json_encode($cartUpdate);

            // Use JavaScript to update localStorage and redirect
            echo '<script type="text/javascript">';
            echo '    let currentCart = JSON.parse(localStorage.getItem(\'dummyCart\') || \'{}\');';
            echo '    let newItems = ' . $cartDataJson . ';';
            echo '    for (let itemId in newItems) {';
            echo '        if (currentCart[itemId]) {';
            echo '            currentCart[itemId].quantity += newItems[itemId].quantity;';
            echo '        } else {';
            echo '            currentCart[itemId] = newItems[itemId];';
            echo '        }';
            echo '    }';
            echo '    localStorage.setItem(\'dummyCart\', JSON.stringify(currentCart));';
            echo '    window.location.href = \'' . $pharmacy_base_url . '/views/customers/cart.php\';';
            echo '</script>';
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Prescription</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $pharmacy_base_url; ?>/public/assets/css/styles.css">
</head>
<body>
    <?php include __DIR__ . '/../header.php'; // Adjust path as necessary ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h2>Upload Prescription</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($uploadMessage)): ?>
                            <div class="alert alert-info" role="alert">
                                <?php echo $uploadMessage; ?>
                            </div>
                        <?php endif; ?>

                        <form action="upload.php" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="prescriptionFile" class="form-label">Select Prescription Image/PDF</label>
                                <input class="form-control" type="file" id="prescriptionFile" name="prescriptionFile" accept=".jpg,.jpeg,.png,.pdf" required>
                            </div>
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes (Optional)</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Upload</button>
                        </form>

                        <?php if (!empty($analysisResult)): ?>
                            <hr>
                            <h3>Analysis Result</h3>
                            <div class="alert alert-secondary">
                                <p><?php echo htmlspecialchars($analysisResult); ?></p>
                            </div>
                            <form action="<?php echo $pharmacy_base_url; ?>/views/prescriptions/upload.php" method="POST">
                                <!-- Debug: Form action URL is <?php echo htmlspecialchars($pharmacy_base_url); ?>/views/prescriptions/upload.php -->
                                <input type="hidden" name="analysisResultText" value="<?php echo htmlspecialchars($analysisResult); ?>">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="confirmCorrect" name="confirmCorrect" value="yes" required>
                                    <label class="form-check-label" for="confirmCorrect">
                                        I confirm the analyzed information is correct.
                                    </label>
                                </div>
                                <button type="submit" class="btn btn-success">Confirm Analysis and Add to Cart</button>
                            </form>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../footer.php'; // Adjust path as necessary ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
