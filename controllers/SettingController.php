<?php

class SettingController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('master_edit', 'write');
        $model = new AppSetting();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = (string)$this->request('action', 'upload_logo');
            if ($action === 'save_invoice_theme') {
                $this->saveInvoiceTheme($model);
            } else {
                $this->uploadLogo($model);
            }
            redirect('setting/index');
        }

        $this->view('settings/index', [
            'logoPath' => $model->get('invoice_logo', ''),
            'invoiceTheme' => $model->get('invoice_theme', 'classic'),
            'invoiceAccentColor' => $model->get('invoice_accent_color', '#1d6f5f'),
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

    private function saveInvoiceTheme(AppSetting $model): void
    {
        $theme = strtolower(trim((string)$this->request('invoice_theme', 'classic')));
        $accent = trim((string)$this->request('invoice_accent_color', '#1d6f5f'));
        $allowedThemes = ['classic', 'minimal', 'premium'];

        if (!in_array($theme, $allowedThemes, true)) {
            flash('error', 'Invalid theme selected.');
            return;
        }

        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $accent)) {
            flash('error', 'Accent color must be a valid hex color (e.g., #1d6f5f).');
            return;
        }

        $model->set('invoice_theme', $theme);
        $model->set('invoice_accent_color', $accent);
        audit_log('update_invoice_theme', 'app_setting', 0, ['theme' => $theme, 'accent' => $accent]);
        flash('success', 'Invoice theme settings updated.');
    }
}
