function getLogoUrl(array $config): string
{
    if (!empty($config['logo'])) {

        $file = __DIR__ . '/../public/assets/' . $config['logo'];

        if (file_exists($file)) {
            return 'assets/' . $config['logo'];
        }
    }

    return 'assets/logo-default.png';
}