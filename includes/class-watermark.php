<?php
/**
 * Watermark Processing Class
 * Handles applying and removing watermarks from images
 * 
 * Key features:
 * - Creates separate watermarked copies (original untouched)
 * - Tracks watermark status via attachment metadata
 * - Supports both GD and Imagick libraries
 * - Proper PNG transparency handling
 */

if (!defined('ABSPATH')) {
    exit;
}

class EKWA_BAG_Watermark {
    
    /**
     * Available image library
     */
    private $library = null;
    
    /**
     * Settings
     */
    private $settings = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->detect_library();
        $this->load_settings();
    }
    
    /**
     * Reload settings (useful after settings are saved)
     */
    public function reload_settings() {
        $this->load_settings();
    }
    
    /**
     * Detect available image processing library
     */
    private function detect_library() {
        if (extension_loaded('imagick') && class_exists('Imagick')) {
            $this->library = 'imagick';
        } elseif (extension_loaded('gd') && function_exists('gd_info')) {
            $this->library = 'gd';
        }
    }
    
    /**
     * Load watermark settings
     */
    private function load_settings() {
        $saved = get_option('ekwa_bag_settings', array());
        $this->settings = wp_parse_args($saved, array(
            'watermark_enabled'   => 0,
            'watermark_type'      => 'text',
            'watermark_text'      => '',
            'watermark_image'     => 0,
            'watermark_position'  => 'bottom-right',
            'watermark_opacity'   => 50,
            'watermark_size'      => 20,
            'watermark_color'     => '#ffffff',
            'watermark_padding'   => 10,
            'image_quality'       => 90,
        ));
    }
    
    /**
     * Check if watermarking is available
     */
    public function is_available() {
        return $this->library !== null;
    }
    
    /**
     * Get the active library name
     */
    public function get_library() {
        return $this->library;
    }
    
    /**
     * Check if watermarking is enabled
     */
    public function is_enabled() {
        return (bool) $this->settings['watermark_enabled'];
    }
    
    /**
     * Get current settings for debugging
     */
    public function get_settings() {
        return $this->settings;
    }
    
    /**
     * Check if watermark is properly configured (has content)
     */
    public function is_configured() {
        if (!$this->is_enabled()) {
            return false;
        }
        
        if ($this->settings['watermark_type'] === 'text') {
            return !empty($this->settings['watermark_text']);
        } else {
            return !empty($this->settings['watermark_image']);
        }
    }
    
    /**
     * Get watermarked file path from original
     */
    public function get_watermarked_path($original_path) {
        $info = pathinfo($original_path);
        return $info['dirname'] . '/' . $info['filename'] . '_watermarked.' . $info['extension'];
    }
    
    /**
     * Apply watermark to an image - creates a separate watermarked copy
     * 
     * @param int $attachment_id The attachment ID
     * @param bool $force Force re-apply even if already watermarked
     * @return array|WP_Error Result with new image path or error
     */
    public function apply_watermark($attachment_id, $force = false) {
        // Reload settings to get latest
        $this->reload_settings();
        
        if (!$this->is_available()) {
            return new WP_Error('no_library', __('No image processing library available.', 'ekwa-before-after-gallery'));
        }
        
        if (!$this->settings['watermark_enabled']) {
            return new WP_Error('disabled', __('Watermark is disabled in settings.', 'ekwa-before-after-gallery'));
        }
        
        // Validate watermark content exists
        if ($this->settings['watermark_type'] === 'text') {
            if (empty($this->settings['watermark_text'])) {
                return new WP_Error('no_text', __('Watermark text is empty. Please set watermark text in settings.', 'ekwa-before-after-gallery'));
            }
        } else {
            if (empty($this->settings['watermark_image'])) {
                return new WP_Error('no_image', __('Watermark image is not set. Please select a watermark image in settings.', 'ekwa-before-after-gallery'));
            }
            // Verify watermark image exists
            $wm_path = get_attached_file($this->settings['watermark_image']);
            if (!$wm_path || !file_exists($wm_path)) {
                return new WP_Error('wm_not_found', __('Watermark image file not found.', 'ekwa-before-after-gallery'));
            }
        }
        
        // Skip if already watermarked (unless forced)
        if (!$force && $this->is_watermarked($attachment_id)) {
            return new WP_Error('already_watermarked', __('Image already has watermark.', 'ekwa-before-after-gallery'));
        }
        
        $original_path = get_attached_file($attachment_id);
        if (!$original_path || !file_exists($original_path)) {
            return new WP_Error('file_not_found', __('Image file not found.', 'ekwa-before-after-gallery'));
        }
        
        // Create watermarked copy path
        $watermarked_path = $this->get_watermarked_path($original_path);
        
        // Copy original to watermarked path
        if (!copy($original_path, $watermarked_path)) {
            return new WP_Error('copy_failed', __('Could not create watermarked copy.', 'ekwa-before-after-gallery'));
        }
        
        // Apply watermark to the copy
        if ($this->library === 'imagick') {
            $result = $this->apply_watermark_imagick($watermarked_path);
        } else {
            $result = $this->apply_watermark_gd($watermarked_path);
        }
        
        if (is_wp_error($result)) {
            @unlink($watermarked_path); // Clean up failed copy
            return $result;
        }
        
        // Store metadata - normalize path for Windows compatibility
        update_post_meta($attachment_id, '_ekwa_bag_watermarked', 1);
        update_post_meta($attachment_id, '_ekwa_bag_watermark_date', current_time('mysql'));
        update_post_meta($attachment_id, '_ekwa_bag_watermarked_file', wp_normalize_path($watermarked_path));
        update_post_meta($attachment_id, '_ekwa_bag_watermark_version', time()); // For cache busting
        
        // Debug log
        error_log(sprintf(
            'EKWA Watermark Applied: ID=%d, Original=%s, Watermarked=%s, Size=%d bytes, Path stored=%s',
            $attachment_id,
            basename($original_path),
            basename($watermarked_path),
            filesize($watermarked_path),
            wp_normalize_path($watermarked_path)
        ));
        
        return array(
            'success'          => true,
            'original_path'    => $original_path,
            'watermarked_path' => $watermarked_path,
            'version'          => get_post_meta($attachment_id, '_ekwa_bag_watermark_version', true),
        );
    }
    
    /**
     * Apply watermark using Imagick
     */
    private function apply_watermark_imagick($file_path) {
        try {
            $image = new Imagick($file_path);
            
            // Handle alpha channel properly
            $image->setImageAlphaChannel(Imagick::ALPHACHANNEL_SET);
            
            $width = $image->getImageWidth();
            $height = $image->getImageHeight();
            
            if ($this->settings['watermark_type'] === 'text') {
                $this->apply_text_watermark_imagick($image, $width, $height);
            } else {
                $this->apply_image_watermark_imagick($image, $width, $height);
            }
            
            $image->setImageCompressionQuality($this->settings['image_quality']);
            $image->writeImage($file_path);
            $image->destroy();
            
            return true;
        } catch (Exception $e) {
            return new WP_Error('imagick_error', $e->getMessage());
        }
    }
    
    /**
     * Apply text watermark using Imagick
     */
    private function apply_text_watermark_imagick($image, $width, $height) {
        $text = $this->settings['watermark_text'];
        if (empty($text)) {
            return;
        }
        
        $draw = new ImagickDraw();
        $draw->setFillColor(new ImagickPixel($this->settings['watermark_color']));
        $draw->setFillOpacity($this->settings['watermark_opacity'] / 100);
        $draw->setFontSize($this->settings['watermark_size']);
        
        // Try to use a system font
        $font_path = $this->get_font_path();
        if ($font_path) {
            $draw->setFont($font_path);
        }
        
        // Get text dimensions
        $metrics = $image->queryFontMetrics($draw, $text);
        $text_width = $metrics['textWidth'];
        $text_height = $metrics['textHeight'];
        
        // Calculate position
        list($x, $y) = $this->calculate_position($width, $height, $text_width, $text_height);
        
        // Set gravity based on position
        $draw->setGravity($this->get_imagick_gravity());
        
        $image->annotateImage($draw, $this->settings['watermark_padding'], $this->settings['watermark_padding'], 0, $text);
    }
    
    /**
     * Apply image watermark using Imagick
     */
    private function apply_image_watermark_imagick($image, $width, $height) {
        $watermark_id = $this->settings['watermark_image'];
        if (!$watermark_id) {
            return;
        }
        
        $watermark_path = get_attached_file($watermark_id);
        if (!$watermark_path || !file_exists($watermark_path)) {
            return;
        }
        
        try {
            $watermark = new Imagick($watermark_path);
            
            // Ensure alpha channel is preserved
            $watermark->setImageAlphaChannel(Imagick::ALPHACHANNEL_SET);
            
            // Resize watermark if needed (max 30% of image width)
            $max_width = $width * 0.3;
            $wm_width = $watermark->getImageWidth();
            $wm_height = $watermark->getImageHeight();
            
            if ($wm_width > $max_width) {
                $ratio = $max_width / $wm_width;
                $watermark->resizeImage(
                    (int)$max_width, 
                    (int)($wm_height * $ratio), 
                    Imagick::FILTER_LANCZOS, 
                    1
                );
                $wm_width = $watermark->getImageWidth();
                $wm_height = $watermark->getImageHeight();
            }
            
            // Set opacity while preserving transparency
            $opacity = $this->settings['watermark_opacity'] / 100;
            $watermark->evaluateImage(Imagick::EVALUATE_MULTIPLY, $opacity, Imagick::CHANNEL_ALPHA);
            
            // Calculate position
            list($x, $y) = $this->calculate_position($width, $height, $wm_width, $wm_height);
            
            // Composite with proper alpha handling
            $image->compositeImage($watermark, Imagick::COMPOSITE_OVER, (int)$x, (int)$y);
            $watermark->destroy();
        } catch (Exception $e) {
            // Log error but don't fail
            error_log('EKWA BAG Watermark Imagick Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Apply watermark using GD
     */
    private function apply_watermark_gd($file_path) {
        $image_info = getimagesize($file_path);
        if (!$image_info) {
            return new WP_Error('invalid_image', __('Could not read image information.', 'ekwa-before-after-gallery'));
        }
        
        $mime = $image_info['mime'];
        $width = $image_info[0];
        $height = $image_info[1];
        
        // Load image based on type
        switch ($mime) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($file_path);
                break;
            case 'image/png':
                $image = imagecreatefrompng($file_path);
                // Enable alpha blending and save alpha
                imagealphablending($image, true);
                imagesavealpha($image, true);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($file_path);
                break;
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    $image = imagecreatefromwebp($file_path);
                } else {
                    return new WP_Error('unsupported', __('WebP format not supported.', 'ekwa-before-after-gallery'));
                }
                break;
            default:
                return new WP_Error('unsupported', __('Unsupported image format.', 'ekwa-before-after-gallery'));
        }
        
        if (!$image) {
            return new WP_Error('load_failed', __('Could not load image.', 'ekwa-before-after-gallery'));
        }
        
        if ($this->settings['watermark_type'] === 'text') {
            $this->apply_text_watermark_gd($image, $width, $height);
        } else {
            $this->apply_image_watermark_gd($image, $width, $height);
        }
        
        // Save image
        switch ($mime) {
            case 'image/jpeg':
                imagejpeg($image, $file_path, $this->settings['image_quality']);
                break;
            case 'image/png':
                imagesavealpha($image, true);
                imagepng($image, $file_path, 9 - round(($this->settings['image_quality'] / 100) * 9));
                break;
            case 'image/gif':
                imagegif($image, $file_path);
                break;
            case 'image/webp':
                if (function_exists('imagewebp')) {
                    imagewebp($image, $file_path, $this->settings['image_quality']);
                }
                break;
        }
        
        imagedestroy($image);
        
        return true;
    }
    
    /**
     * Apply text watermark using GD
     */
    private function apply_text_watermark_gd($image, $width, $height) {
        $text = $this->settings['watermark_text'];
        if (empty($text)) {
            return;
        }
        
        // Enable alpha blending for the text
        imagealphablending($image, true);
        
        // Parse color
        $color = $this->hex_to_rgb($this->settings['watermark_color']);
        $alpha = 127 - round(($this->settings['watermark_opacity'] / 100) * 127);
        
        $text_color = imagecolorallocatealpha($image, $color['r'], $color['g'], $color['b'], $alpha);
        
        // Get font path
        $font_path = $this->get_font_path();
        $font_size = $this->settings['watermark_size'];
        
        if ($font_path && function_exists('imagettfbbox')) {
            // Use TrueType font
            $bbox = imagettfbbox($font_size, 0, $font_path, $text);
            $text_width = abs($bbox[4] - $bbox[0]);
            $text_height = abs($bbox[5] - $bbox[1]);
            
            list($x, $y) = $this->calculate_position($width, $height, $text_width, $text_height);
            
            imagettftext($image, $font_size, 0, $x, $y + $text_height, $text_color, $font_path, $text);
        } else {
            // Use built-in font
            $font = 5; // Largest built-in font
            $text_width = imagefontwidth($font) * strlen($text);
            $text_height = imagefontheight($font);
            
            list($x, $y) = $this->calculate_position($width, $height, $text_width, $text_height);
            
            imagestring($image, $font, $x, $y, $text, $text_color);
        }
    }
    
    /**
     * Apply image watermark using GD - with proper PNG transparency support
     */
    private function apply_image_watermark_gd($image, $width, $height) {
        $watermark_id = $this->settings['watermark_image'];
        if (!$watermark_id) {
            return;
        }
        
        $watermark_path = get_attached_file($watermark_id);
        if (!$watermark_path || !file_exists($watermark_path)) {
            return;
        }
        
        $wm_info = getimagesize($watermark_path);
        if (!$wm_info) {
            return;
        }
        
        // Load watermark with proper alpha handling
        $watermark = null;
        switch ($wm_info['mime']) {
            case 'image/jpeg':
                $watermark = imagecreatefromjpeg($watermark_path);
                break;
            case 'image/png':
                $watermark = imagecreatefrompng($watermark_path);
                // Critical: preserve PNG transparency
                imagealphablending($watermark, true);
                imagesavealpha($watermark, true);
                break;
            case 'image/gif':
                $watermark = imagecreatefromgif($watermark_path);
                break;
            default:
                return;
        }
        
        if (!$watermark) {
            return;
        }
        
        $wm_width = $wm_info[0];
        $wm_height = $wm_info[1];
        
        // Resize if needed
        $max_width = $width * 0.3;
        if ($wm_width > $max_width) {
            $ratio = $max_width / $wm_width;
            $new_width = (int)round($wm_width * $ratio);
            $new_height = (int)round($wm_height * $ratio);
            
            // Create true color image with alpha support
            $resized = imagecreatetruecolor($new_width, $new_height);
            
            // Critical: Handle transparency properly
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            
            // Fill with transparent background
            $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
            imagefilledrectangle($resized, 0, 0, $new_width, $new_height, $transparent);
            
            // Resample with alpha
            imagealphablending($resized, true);
            imagecopyresampled($resized, $watermark, 0, 0, 0, 0, $new_width, $new_height, $wm_width, $wm_height);
            
            imagedestroy($watermark);
            $watermark = $resized;
            $wm_width = $new_width;
            $wm_height = $new_height;
        }
        
        // Calculate position
        list($x, $y) = $this->calculate_position($width, $height, $wm_width, $wm_height);
        
        // Apply watermark with transparency using custom function
        $opacity = $this->settings['watermark_opacity'];
        $this->imagecopymerge_alpha($image, $watermark, (int)$x, (int)$y, 0, 0, $wm_width, $wm_height, $opacity);
        
        imagedestroy($watermark);
    }
    
    /**
     * Custom imagecopymerge that preserves alpha transparency
     * Standard imagecopymerge doesn't handle PNG transparency correctly
     */
    private function imagecopymerge_alpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct) {
        if ($pct === 100) {
            // Full opacity, use standard copy
            imagecopy($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h);
            return;
        }
        
        // Create a temporary image with the same dimensions as watermark
        $cut = imagecreatetruecolor($src_w, $src_h);
        
        // Preserve transparency
        imagealphablending($cut, false);
        imagesavealpha($cut, true);
        
        // Copy the destination area to temp
        imagecopy($cut, $dst_im, 0, 0, $dst_x, $dst_y, $src_w, $src_h);
        
        // Copy the watermark onto temp with proper alpha handling
        // We need to manually blend pixels
        for ($x = 0; $x < $src_w; $x++) {
            for ($y = 0; $y < $src_h; $y++) {
                $src_color = imagecolorat($src_im, $x + $src_x, $y + $src_y);
                $src_a = ($src_color >> 24) & 0x7F;
                
                // If source pixel is fully transparent, skip
                if ($src_a === 127) {
                    continue;
                }
                
                $src_r = ($src_color >> 16) & 0xFF;
                $src_g = ($src_color >> 8) & 0xFF;
                $src_b = $src_color & 0xFF;
                
                $dst_color = imagecolorat($cut, $x, $y);
                $dst_r = ($dst_color >> 16) & 0xFF;
                $dst_g = ($dst_color >> 8) & 0xFF;
                $dst_b = $dst_color & 0xFF;
                
                // Calculate effective opacity
                $src_opacity = (127 - $src_a) / 127;
                $pct_opacity = $pct / 100;
                $final_opacity = $src_opacity * $pct_opacity;
                
                // Blend colors
                $new_r = (int)round($src_r * $final_opacity + $dst_r * (1 - $final_opacity));
                $new_g = (int)round($src_g * $final_opacity + $dst_g * (1 - $final_opacity));
                $new_b = (int)round($src_b * $final_opacity + $dst_b * (1 - $final_opacity));
                
                $new_color = imagecolorallocate($cut, $new_r, $new_g, $new_b);
                imagesetpixel($cut, $x, $y, $new_color);
            }
        }
        
        // Copy blended result back to destination
        imagecopy($dst_im, $cut, $dst_x, $dst_y, 0, 0, $src_w, $src_h);
        imagedestroy($cut);
    }
    
    /**
     * Calculate position based on settings
     */
    private function calculate_position($img_width, $img_height, $wm_width, $wm_height) {
        $padding = $this->settings['watermark_padding'];
        $position = $this->settings['watermark_position'];
        
        switch ($position) {
            case 'top-left':
                $x = $padding;
                $y = $padding;
                break;
            case 'top-center':
                $x = ($img_width - $wm_width) / 2;
                $y = $padding;
                break;
            case 'top-right':
                $x = $img_width - $wm_width - $padding;
                $y = $padding;
                break;
            case 'middle-left':
                $x = $padding;
                $y = ($img_height - $wm_height) / 2;
                break;
            case 'middle-center':
                $x = ($img_width - $wm_width) / 2;
                $y = ($img_height - $wm_height) / 2;
                break;
            case 'middle-right':
                $x = $img_width - $wm_width - $padding;
                $y = ($img_height - $wm_height) / 2;
                break;
            case 'bottom-left':
                $x = $padding;
                $y = $img_height - $wm_height - $padding;
                break;
            case 'bottom-center':
                $x = ($img_width - $wm_width) / 2;
                $y = $img_height - $wm_height - $padding;
                break;
            case 'bottom-right':
            default:
                $x = $img_width - $wm_width - $padding;
                $y = $img_height - $wm_height - $padding;
                break;
        }
        
        return array(round($x), round($y));
    }
    
    /**
     * Get Imagick gravity constant
     */
    private function get_imagick_gravity() {
        $map = array(
            'top-left'      => Imagick::GRAVITY_NORTHWEST,
            'top-center'    => Imagick::GRAVITY_NORTH,
            'top-right'     => Imagick::GRAVITY_NORTHEAST,
            'middle-left'   => Imagick::GRAVITY_WEST,
            'middle-center' => Imagick::GRAVITY_CENTER,
            'middle-right'  => Imagick::GRAVITY_EAST,
            'bottom-left'   => Imagick::GRAVITY_SOUTHWEST,
            'bottom-center' => Imagick::GRAVITY_SOUTH,
            'bottom-right'  => Imagick::GRAVITY_SOUTHEAST,
        );
        
        return $map[$this->settings['watermark_position']] ?? Imagick::GRAVITY_SOUTHEAST;
    }
    
    /**
     * Get font path for text watermarks
     */
    private function get_font_path() {
        // Try plugin fonts directory first
        $plugin_font = EKWA_BAG_PLUGIN_DIR . 'assets/fonts/OpenSans-Regular.ttf';
        if (file_exists($plugin_font)) {
            return $plugin_font;
        }
        
        // Try common system fonts
        $system_fonts = array(
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/TTF/DejaVuSans.ttf',
            'C:/Windows/Fonts/arial.ttf',
            'C:/Windows/Fonts/segoeui.ttf',
        );
        
        foreach ($system_fonts as $font) {
            if (file_exists($font)) {
                return $font;
            }
        }
        
        return false;
    }
    
    /**
     * Convert hex color to RGB
     */
    private function hex_to_rgb($hex) {
        $hex = ltrim($hex, '#');
        
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        
        return array(
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2)),
        );
    }
    
    /**
     * Remove watermark - deletes watermarked copy, original is untouched
     */
    public function remove_watermark($attachment_id) {
        $watermarked_file = get_post_meta($attachment_id, '_ekwa_bag_watermarked_file', true);
        
        // Delete the watermarked copy if it exists
        if ($watermarked_file && file_exists($watermarked_file)) {
            @unlink($watermarked_file);
        }
        
        // Clear metadata
        delete_post_meta($attachment_id, '_ekwa_bag_watermarked');
        delete_post_meta($attachment_id, '_ekwa_bag_watermark_date');
        delete_post_meta($attachment_id, '_ekwa_bag_watermarked_file');
        delete_post_meta($attachment_id, '_ekwa_bag_watermark_version');
        
        return array(
            'success' => true,
            'message' => __('Watermark removed successfully.', 'ekwa-before-after-gallery'),
        );
    }
    
    /**
     * Check if an image is watermarked
     */
    public function is_watermarked($attachment_id) {
        return (bool) get_post_meta($attachment_id, '_ekwa_bag_watermarked', true);
    }
    
    /**
     * Clear watermark metadata (useful for debugging)
     */
    public function clear_watermark_status($attachment_id) {
        delete_post_meta($attachment_id, '_ekwa_bag_watermarked');
        delete_post_meta($attachment_id, '_ekwa_bag_watermark_date');
        delete_post_meta($attachment_id, '_ekwa_bag_watermarked_file');
        delete_post_meta($attachment_id, '_ekwa_bag_watermark_version');
        return true;
    }
    
    /**
     * Get watermark info for debugging
     */
    public function get_watermark_info($attachment_id) {
        $original_path = get_attached_file($attachment_id);
        $watermarked_path = $this->get_watermarked_path($original_path);
        
        return array(
            'is_watermarked' => $this->is_watermarked($attachment_id),
            'original_path' => $original_path,
            'original_exists' => file_exists($original_path),
            'watermarked_path' => $watermarked_path,
            'watermarked_exists' => file_exists($watermarked_path),
            'watermarked_file_meta' => get_post_meta($attachment_id, '_ekwa_bag_watermarked_file', true),
            'version' => get_post_meta($attachment_id, '_ekwa_bag_watermark_version', true),
        );
    }
    
    /**
     * Get watermarked image URL for display (with cache busting)
     */
    public function get_watermarked_url($attachment_id, $size = 'large') {
        if (!$this->is_watermarked($attachment_id)) {
            error_log("EKWA get_watermarked_url: ID=$attachment_id is NOT marked as watermarked");
            return wp_get_attachment_image_url($attachment_id, $size);
        }
        
        $watermarked_file = get_post_meta($attachment_id, '_ekwa_bag_watermarked_file', true);
        
        // Normalize path for cross-platform compatibility
        if ($watermarked_file) {
            $watermarked_file = wp_normalize_path($watermarked_file);
        }
        
        $version = get_post_meta($attachment_id, '_ekwa_bag_watermark_version', true);
        
        error_log("EKWA get_watermarked_url: ID=$attachment_id, watermarked_file=$watermarked_file, exists=" . (file_exists($watermarked_file) ? 'YES' : 'NO'));
        
        if ($watermarked_file && file_exists($watermarked_file)) {
            // Convert file path to URL
            $upload_dir = wp_upload_dir();
            $basedir = wp_normalize_path($upload_dir['basedir']);
            $baseurl = $upload_dir['baseurl'];
            
            // Replace basedir with baseurl, ensuring forward slashes
            $url = str_replace($basedir, $baseurl, $watermarked_file);
            
            // Add cache busting parameter
            if ($version) {
                $url .= '?v=' . $version;
            }
            
            error_log("EKWA get_watermarked_url: basedir=$basedir, baseurl=$baseurl, Returning URL: $url");
            return $url;
        }
        
        // Fallback to original
        error_log("EKWA get_watermarked_url: Falling back to original for ID=$attachment_id");
        return wp_get_attachment_image_url($attachment_id, $size);
    }
    
    /**
     * Get image URL - returns watermarked version if available, otherwise original
     */
    public function get_display_url($attachment_id, $size = 'large') {
        if ($this->is_watermarked($attachment_id)) {
            return $this->get_watermarked_url($attachment_id, $size);
        }
        return wp_get_attachment_image_url($attachment_id, $size);
    }
    
    /**
     * Get all gallery image IDs
     */
    public function get_gallery_image_ids() {
        $image_ids = array();
        
        $cases = get_posts(array(
            'post_type'      => 'ekwa_bag_case',
            'posts_per_page' => -1,
            'post_status'    => 'any',
        ));
        
        foreach ($cases as $case) {
            $image_sets = get_post_meta($case->ID, '_ekwa_bag_image_sets', true);
            
            if (!empty($image_sets) && is_array($image_sets)) {
                foreach ($image_sets as $set) {
                    if (!empty($set['before'])) {
                        $image_ids[] = absint($set['before']);
                    }
                    if (!empty($set['after'])) {
                        $image_ids[] = absint($set['after']);
                    }
                }
            }
        }
        
        return array_unique($image_ids);
    }
    
    /**
     * Apply watermarks to a case's images
     * @param int $case_id The case post ID
     * @param bool $force Force re-apply even if already watermarked
     */
    public function apply_watermarks_to_case($case_id, $force = false) {
        if (!$this->is_enabled() || !$this->is_available()) {
            return array('skipped' => true, 'reason' => 'Watermark disabled or unavailable');
        }
        
        $image_sets = get_post_meta($case_id, '_ekwa_bag_image_sets', true);
        if (empty($image_sets) || !is_array($image_sets)) {
            return array('skipped' => true, 'reason' => 'No images');
        }
        
        $results = array('success' => 0, 'skipped' => 0, 'failed' => 0, 'errors' => array());
        
        foreach ($image_sets as $set) {
            foreach (array('before', 'after') as $type) {
                if (!empty($set[$type])) {
                    $attachment_id = absint($set[$type]);
                    
                    // Skip if already watermarked (unless forced)
                    if (!$force && $this->is_watermarked($attachment_id)) {
                        $results['skipped']++;
                        continue;
                    }
                    
                    // Force re-apply by removing old watermark first
                    if ($force && $this->is_watermarked($attachment_id)) {
                        $this->remove_watermark($attachment_id);
                    }
                    
                    $result = $this->apply_watermark($attachment_id);
                    if (is_wp_error($result)) {
                        $results['failed']++;
                        $results['errors'][] = array(
                            'id' => $attachment_id,
                            'error' => $result->get_error_message()
                        );
                    } else {
                        $results['success']++;
                    }
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Bulk apply watermarks
     */
    public function bulk_apply_watermarks($image_ids = null) {
        // Reload settings
        $this->reload_settings();
        
        if ($image_ids === null) {
            $image_ids = $this->get_gallery_image_ids();
        }
        
        $results = array(
            'total'     => count($image_ids),
            'success'   => 0,
            'failed'    => 0,
            'skipped'   => 0,
            'errors'    => array(),
        );
        
        foreach ($image_ids as $id) {
            // Skip if already watermarked - don't re-apply!
            if ($this->is_watermarked($id)) {
                $results['skipped']++;
                continue;
            }
            
            $result = $this->apply_watermark($id);
            
            if (is_wp_error($result)) {
                $results['failed']++;
                $results['errors'][] = array(
                    'id'    => $id,
                    'error' => $result->get_error_message(),
                );
            } else {
                $results['success']++;
            }
        }
        
        return $results;
    }
    
    /**
     * Bulk remove watermarks
     */
    public function bulk_remove_watermarks($image_ids = null) {
        if ($image_ids === null) {
            $image_ids = $this->get_gallery_image_ids();
        }
        
        $results = array(
            'total'     => count($image_ids),
            'success'   => 0,
            'failed'    => 0,
            'skipped'   => 0,
            'errors'    => array(),
        );
        
        foreach ($image_ids as $id) {
            // Skip if not watermarked
            if (!$this->is_watermarked($id)) {
                $results['skipped']++;
                continue;
            }
            
            $result = $this->remove_watermark($id);
            
            if (is_wp_error($result)) {
                $results['failed']++;
                $results['errors'][] = array(
                    'id'    => $id,
                    'error' => $result->get_error_message(),
                );
            } else {
                $results['success']++;
            }
        }
        
        return $results;
    }
}
