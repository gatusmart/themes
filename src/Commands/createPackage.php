<?php namespace Laraone\Themes\Commands;

use Illuminate\Console\Command;
use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class createPackage extends baseCommand
{
    protected $signature = 'theme:package {themeName?}';
    protected $description = 'Create a theme package';

    public function handle() {
        if (extension_loaded('zip')) {
            $themeName = $this->argument('themeName');

            if ($themeName == ""){
                $themes = array_map(function($theme){
                    return $theme->name;
                }, \Theme::all());
                $themeName = $this->choice('Select a theme to create a distributable package:', $themes);
            }
            $theme = \Theme::find($themeName);

            $viewsPath = themes_path($theme->viewsPath);
            $assetPath = public_path($theme->assetPath);

            // Packages storage path
            $packagesPath = $this->packages_path();
            if(!$this->files->exists($packagesPath))
                mkdir($packagesPath);

            // Sanitize target filename
            $packageFileName = $theme->name;
            $packageFileName = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $packageFileName);
            $packageFileName = mb_ereg_replace("([\.]{2,})", '', $packageFileName);
            $packageFileName = $this->packages_path("{$packageFileName}.zip");
            $packageFileName = strtolower(str_replace(' ', '-', $packageFileName));

            // Create Temp Folder
            $this->createTempFolder();

            // Copy Views+Assets to Temp Folder
            // system("cp -r $viewsPath {$this->tempPath}/views");
            // system("cp -r $assetPath {$this->tempPath}/assets");
            system("cp -r " . $viewsPath . " " . $this->tempPath . DIRECTORY_SEPARATOR . "views");
            system("cp -r " . $assetPath . " " . $this->tempPath . DIRECTORY_SEPARATOR . "assets");

            // Create ZipArchive Obj
            $zip = new ZipArchive;
            if ($zip->open($packageFileName, (ZipArchive::CREATE | ZipArchive::OVERWRITE)) === TRUE) {

                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($this->tempPath),
                    RecursiveIteratorIterator::LEAVES_ONLY
                );

                foreach ($files as $name => $file)
                {
                    // Skip directories (they would be added automatically)
                    if (!$file->isDir())
                    {
                        // Get real and relative path for current file
                        $filePath = $file->getRealPath();
                        $relativePath = substr($filePath, strlen($this->tempPath) + 1);

                        // Add current file to archive
                        $zip->addFile(str_replace('\\', '/', $filePath), str_replace('\\', '/', $relativePath));
                    }
                }

                // Close ZipArchive
                $zip->close();
            }

            // Del Temp Folder
            $this->clearTempFolder();

            $this->info("Theme Package created at [$packageFileName]");
        } else {
            $this->info("Zip extension needs to be enabled for this to work.");
        }
    }

}
