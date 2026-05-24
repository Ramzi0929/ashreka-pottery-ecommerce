<?php
class UploadHandler {
    public function uploadProductImages($files, $product_id) {
        $uploaded_files = [];
        
        foreach ($files['tmp_name'] as $key => $tmp_name) {
            if ($files['error'][$key] === UPLOAD_ERR_OK) {
                $filename = $this->generateFilename($files['name'][$key], $product_id);
                $destination = PRODUCT_IMAGE_PATH . $filename;
                
                if (move_uploaded_file($tmp_name, $destination)) {
                    $uploaded_files[] = $filename;
                    $this->saveProductImage($product_id, $filename);
                }
            }
        }
        
        return $uploaded_files;
    }
    
    public function uploadProductVideo($file, $product_id) {
        if ($file['error'] === UPLOAD_ERR_OK) {
            $filename = $this->generateFilename($file['name'], $product_id, 'video');
            $destination = PRODUCT_IMAGE_PATH . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $this->saveProductVideo($product_id, $filename);
                return $filename;
            }
        }
        return false;
    }
    
    private function generateFilename($original_name, $product_id, $type = 'image') {
        $extension = getFileExtension($original_name);
        $timestamp = time();
        return "product_{$product_id}_{$type}_{$timestamp}.{$extension}";
    }
    
    private function saveProductImage($product_id, $filename) {
        $db = (new Database())->getConnection();
        $query = "INSERT INTO product_images (product_id, image_path) VALUES (?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([$product_id, $filename]);
    }
    
    private function saveProductVideo($product_id, $filename) {
        $db = (new Database())->getConnection();
        $query = "UPDATE products SET video_path = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$filename, $product_id]);
    }

    // Add these methods to the existing UploadHandler class

public function uploadHeritageFile($file, $heritage_id) {
    if ($file['error'] === UPLOAD_ERR_OK) {
        $filename = $this->generateHeritageFilename($file['name'], $heritage_id);
        $destination = HERITAGE_PATH . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $this->updateHeritageFilepath($heritage_id, $filename);
            return $filename;
        }
    }
    return false;
}

public function uploadProgressImage($file, $order_id) {
    if ($file['error'] === UPLOAD_ERR_OK) {
        $filename = $this->generateProgressFilename($file['name'], $order_id);
        $destination = UPLOAD_PATH . 'progress/' . $filename;
        
        // Create progress directory if it doesn't exist
        if (!is_dir(UPLOAD_PATH . 'progress/')) {
            mkdir(UPLOAD_PATH . 'progress/', 0755, true);
        }
        
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return $filename;
        }
    }
    return false;
}

public function uploadProfileImage($file, $user_id) {
    if ($file['error'] === UPLOAD_ERR_OK) {
        $filename = $this->generateProfileFilename($file['name'], $user_id);
        $destination = PROFILE_IMAGE_PATH . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return $filename;
        }
    }
    return false;
}

private function generateHeritageFilename($original_name, $heritage_id) {
    $extension = getFileExtension($original_name);
    $timestamp = time();
    return "heritage_{$heritage_id}_{$timestamp}.{$extension}";
}

private function generateProgressFilename($original_name, $order_id) {
    $extension = getFileExtension($original_name);
    $timestamp = time();
    return "progress_{$order_id}_{$timestamp}.{$extension}";
}

private function generateProfileFilename($original_name, $user_id) {
    $extension = getFileExtension($original_name);
    $timestamp = time();
    return "profile_{$user_id}_{$timestamp}.{$extension}";
}

private function updateHeritageFilepath($heritage_id, $filename) {
    $db = (new Database())->getConnection();
    $query = "UPDATE heritage_content SET file_path = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    return $stmt->execute([$filename, $heritage_id]);
}
}
?>