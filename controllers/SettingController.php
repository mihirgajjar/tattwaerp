<?php

class SettingController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $model = new AppSetting();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->uploadLogo($model);
            redirect('setting/index');
        }

        $this->view('settings/index', [
            'logoPath' => $model->get('invoice_logo', ''),
        ]);
    }

    private function uploadLogo(AppSetting $model): void
    {
        if (!isset($_FILES['invoice_logo']) || $_FILES['invoice_logo']['error'] === UPLOAD_ERR_NO_FILE) {
            flash('error', 'Please choose an image file.');
            return;
        }

        $file = $_FILES['invoice_logo'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            flash('error', 'Upload failed. Please try again.');
            return;
        }

        if ((int)$file['size'] > 2 * 1024 * 1024) {
            flash('error', 'Logo size must be under 2 MB.');
            return;
        }

        $mime = mime_content_type($file['tmp_name']) ?: '';
        $allowed = [
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
        ];

        if (!isset($allowed[$mime])) {
            flash('error', 'Only PNG, JPG or WEBP files are allowed.');
            return;
        }

        $ext = $allowed[$mime];
        $filename = 'logo_' . time() . '.' . $ext;
        $targetDir = __DIR__ . '/../assets/uploads';

        if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
            flash('error', 'Unable to create upload directory.');
            return;
        }

        if (!is_writable($targetDir)) {
            @chmod($targetDir, 0775);
        }
        if (!is_writable($targetDir)) {
            @chmod($targetDir, 0777);
        }
        if (!is_writable($targetDir)) {
            flash('error', 'Upload directory is not writable. Please set write permission for assets/uploads.');
            return;
        }

        $targetPath = $targetDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Fallback for environments where move_uploaded_file can fail with directory ownership mismatch.
            if (is_uploaded_file($file['tmp_name']) && @copy($file['tmp_name'], $targetPath)) {
                @unlink($file['tmp_name']);
            } else {
                flash('error', 'Could not save uploaded file. Check write permission for assets/uploads.');
                return;
            }
        }
        @chmod($targetPath, 0644);

        $old = $model->get('invoice_logo', '');
        if ($old !== '') {
            $oldPath = __DIR__ . '/../' . $old;
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        $relativePath = 'assets/uploads/' . $filename;
        $model->set('invoice_logo', $relativePath);
        audit_log('update_invoice_logo', 'app_setting', 0, ['path' => $relativePath]);
        flash('success', 'Invoice logo updated.');
    }
}
