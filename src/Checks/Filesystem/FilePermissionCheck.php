<?php

namespace Hexafuchs\LaminasSecurity\Checks\Filesystem;

class FilePermissionCheck extends AbstractFilesystemCheck
{
    private bool $foundInsecurePermissions   = false;
    private bool $expectedFilesThatDontExist = false;

    /**
     * @inheritDoc
     */
    public function getCheckName(): string
    {
        return 'File permissions are secure';
    }

    /**
     * @inheritDoc
     */
    protected function run(): void
    {
        $this->checkRootDirectories();
        $this->checkRootFiles();
        $this->appendSummary();
        $this->finish();
    }

    protected function checkRootDirectories(): void
    {
        $this->scanPathRecursive('bin', 755, 755, false);
        $this->scanPathRecursive('config', 644, 755);
        $this->scanPathRecursive('data', 644, 755);
        $this->scanPathRecursive('module', 644, 755, false);
        $this->scanPathRecursive('public', 644, 755);
        $this->scanPathRecursive('src', 644, 755, false);
        $this->scanPathRecursive('test', 644, 755, false);
    }

    protected function checkRootFiles(): void
    {
        $this->checkFilePerms('composer.json', 644);
        $this->checkFilePerms('composer.lock', 644);
    }

    protected function appendSummary(): void
    {
        if ($this->expectedFilesThatDontExist) {
            $this->appendDetails([
                '',
                'Expected files that did not exist. This could indicate that there is a problem with to strict ' .
                'permissions. Please make sure you execute the command with the user that is used by the webserver ' .
                'using <cmd-warn> sudo -u </><cmd-success>{user} </><cmd-mark>{command} </>'
            ]);
        }

        if ($this->foundInsecurePermissions) {
            $this->appendDetails([
                '',
                'Found files with insecure permissions. Please make sure to change the file permissions of the ' .
                'listed files to the expected value. Insecure permissions can expose your application to compromise ' .
                'if another account on the same server is compromised.',
                '',
                'You can change the permissions of existing files with the following commands:',
                '                   Single File: <cmd-warn> chmod </><cmd-success>{mod} </><cmd-mark>{file} </>',
                '        All files in directory: <cmd-warn> find <cmd-mark>{dir}</> -type d -exec chmod <cmd-success>{mod}</> {} + </>',
                '  All directories in directory: <cmd-warn> find <cmd-mark>{dir}</> -type d -exec chmod <cmd-success>{mod}</> {} + </>'
            ]);
        }
    }

    protected function scanPathRecursive(string $path, int $fileExpectation, int $directoryExpectation, bool $required = true): void
    {
        $fullPath = getcwd() . '/' . $path;

        if (file_exists($fullPath)) {
            if (is_dir($fullPath)) {
                $this->checkFilePerms($path, $directoryExpectation);

                foreach (scandir($fullPath, SCANDIR_SORT_ASCENDING) as $file) {
                    if (in_array($file, ['.', '..'])) {
                        continue;
                    }

                    $this->scanPathRecursive($path . '/' . $file, $fileExpectation, $directoryExpectation);
                }
            } else {
                $this->checkFilePerms($path, $fileExpectation);
            }
        } else if ($required && !is_link($fullPath)) {
            $this->expectedFileNotFound($path);
        }
    }

    protected function checkFilePerms(string $path, int $expected, bool $required = true): void
    {
        $fullPath = getcwd() . '/' . $path;

        if (file_exists($fullPath)) {
            $actual = decoct(fileperms($fullPath) & 0777);

            if ($expected < $actual) {
                $this->insecureFilePermissions(
                    is_dir($fullPath) ? $path . '/' : $path,
                    $expected, $actual
                );
            }
        } else if ($required) {
            $this->expectedFileNotFound($path);
        }
    }

    protected function insecureFilePermissions(string $path, int $expected, int $actual): void
    {
        $this->foundInsecurePermissions = true;
        $this->fail(sprintf(
            '- <fail>%d</> is considered insecure for file <mark>%s</> (expected <success>%d</>)',
            $actual, $path, $expected
        ));
    }

    private function expectedFileNotFound(string $path): void
    {
        $this->expectedFilesThatDontExist = true;
        $this->warn(sprintf(
            '- Expected <mark>%s</> but <warn>could not find it</>.',
            $path
        ));
    }
}