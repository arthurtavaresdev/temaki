# Temaki

**Temaki** is a PHP library that combines the simplicity of [Sushi](https://github.com/calebporzio/sushi) with the flexibility of [sqlite-s3](https://github.com/mnapoli/sqlite-s3/), enabling Laravel applications to seamlessly and efficiently utilize SQLite databases stored on Amazon S3.

## Motivation

When developing serverless applications with Laravel, especially using platforms like AWS Lambda and Laravel Vapor, we encounter significant challenges related to data storage and management due to the ephemeral nature of these environments. The volatility of the file system in Lambda instances prevents local data persistence, making traditional database solutions less effective. Moreover, the need for an SQLite driver that operates efficiently and persistently on Amazon S3 is evident to ensure data integrity and availability. **Temaki** was conceived to address this need by integrating the functionalities of [Sushi](https://github.com/calebporzio/sushi) and [sqlite-s3](https://github.com/mnapoli/sqlite-s3/), providing a robust and efficient solution for data storage in serverless environments.

## How?
The SQLite database (a file) is stored on S3. The PHP class will transparently download the file locally on every request, and upload it back at the end.

## Features

- **Integration with Laravel**: Use Temaki as an Eloquent driver to access data stored in SQLite files on S3.
- **Transparent Synchronization**: Temaki automatically manages the download and upload of the database between S3 and the local instance, ensuring data is always up-to-date.
- **Simplified Configuration**: Simply add the `Temaki` trait to your Eloquent model and configure your S3 credentials to get started.

## Requirements

- PHP 8.3 or higher
- Laravel 11.x or higher
- PHP `pdo_sqlite` extension
- Valid AWS credentials with access to S3 (Also works with minio)

## Installation

You can install Temaki via Composer:

```bash
composer require arthurtavaresdev/temaki
```

## Configuration

Set up S3 credentials:
In your application's .env file, add the following variables:

```env
AWS_ACCESS_KEY_ID=your-access-key-id
AWS_SECRET_ACCESS_KEY=your-secret-access-key
AWS_DEFAULT_REGION=your-region
AWS_BUCKET=your-bucket-name
```
Configure the Eloquent model:

Add the Temaki trait to your model and define the $sushiFilePath property with the path to the SQLite file on S3:

```php
use Temaki\Temaki;

class YourModel extends Model
{
    use Temaki;

    public function getRows()
    {
        return [
            ['id' => 1, 'label' => 'admin'],
            ['id' => 2, 'label' => 'manager'],
            ['id' => 3, 'label' => 'user'],
        ];
    }
}
```
## Usage
After configuration, you can use your Eloquent model as usual, and Temaki will handle the synchronization of data between S3 and the local instance.

## Considerations
Temaki is ideal for development and testing environments in serverless architectures. For production environments, it's important to consider the implications of concurrency and latency associated with using databases stored on S3.

## Contribution
Contributions are welcome! Feel free to open issues and pull requests in the GitHub repository.

## License
This project is licensed under the MIT License.
