<?php declare(strict_types=1);

namespace ArthurTavaresDev\Temaki\Database\SqliteS3;

use Aws\S3\S3Client;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use RuntimeException;

/**
 * @internal
 */
class DbSynchronizer
{
    private string | null $dbFileName = null;
    private string | null $dbFileHash = null;

    private readonly LoggerInterface $logger;

    /**
     * @param string $bucket
     * @param string $key
     */
    public function __construct(
        private readonly string $bucket,
        private readonly string $key
    ) {
        $this->logger = Log::getLogger();

        $this->logger->debug('DbSynchronizer initialized', [
            'bucket' => $this->bucket,
            'key' => $this->key,
        ]);
    }

    /**
     * @return string The path to the DB file name
     */
    public function open(): string
    {

        $this->logger->info('Downloading and opening the SQLite database');

        try {
            $contentAsResource = Storage::disk('s3')->readStream($this->key);
        } catch (\Aws\S3\Exception\S3Exception $e) {
            // The file does not exist yet, create an empty one
            $contentAsResource = fopen('php://memory', 'rb');
        }

        $this->dbFileName = tempnam(sys_get_temp_dir(), 'db.sqlite');
        if ($this->dbFileName === false) {
            throw new RuntimeException('Could not create temporary file');
        }
        $fileResource = fopen($this->dbFileName, 'wb');

        $success = stream_copy_to_stream($contentAsResource, $fileResource);

        if ($success === false) {
            throw new RuntimeException('Could not dump S3 file to temporary file');
        }

        if (! fclose($fileResource)) {
            throw new RuntimeException('Could not close temporary file');
        }

        $this->dbFileHash = md5_file($this->dbFileName);
        if ($this->dbFileHash === false) {
            throw new RuntimeException('Could not calculate MD5 hash');
        }

        return $this->dbFileName;
    }

    public function close(): void
    {
        if (! $this->dbFileName) {
            return;
        }

        $fileChanged = $this->dbFileHash !== md5_file($this->dbFileName);

        $this->logger->info('Closing' . ($fileChanged ? ' and uploading' : '') . ' the SQLite database');

        if ($fileChanged) {
            // Clear the file
            Storage::disk('s3')->put($this->key, '');

            // Upload back to S3
            $contentAsResource = fopen($this->dbFileName, 'rb');
            Storage::disk('s3')->put($this->key, $contentAsResource);
            fclose($contentAsResource);
        }

        unlink($this->dbFileName);

        $this->dbFileName = null;
    }

    public function isOpened(): bool
    {
        return $this->dbFileName !== null;
    }
}
