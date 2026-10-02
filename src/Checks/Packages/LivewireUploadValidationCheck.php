<?php

namespace ShipReady\Checks\Packages;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'LW002',
    title: 'Livewire file upload missing size/type validation rules',
    category: 'packages',
    severity: 'high'
)]
final class LivewireUploadValidationCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        if (!$this->isLivewireInstalled()) {
            return;
        }

        $analyzer = $context->code();

        if ($analyzer === null) {
            return;
        }

        foreach ($analyzer->phpFiles() as $file) {
            $content = file_get_contents($file) ?: '';

            if (!str_contains($content, 'WithFileUploads') && !str_contains($content, 'TemporaryUploadedFile')) {
                continue;
            }

            // Check if there are validation rules for the upload
            if (!preg_match('/[\'"](?:max|mimes|image|mimetypes)[\'"]/', $content)) {
                yield $this->finding(
                    message: 'Livewire component uses file uploads but validation rules for max size, mimes, or file type are missing. Users can upload arbitrary large or dangerous files.',
                    file:    $file,
                    fix:     "Add size and type rules to your validate() call: 'photo' => 'required|image|max:2048|mimes:jpg,png'"
                );
            }
        }
    }

    private function isLivewireInstalled(): bool
    {
        $composerLock = base_path('composer.lock');

        if (!file_exists($composerLock)) {
            return false;
        }

        $lock     = json_decode(file_get_contents($composerLock) ?: '{}', true);
        $packages = array_column($lock['packages'] ?? [], 'name');

        return in_array('livewire/livewire', $packages, true);
    }
}
