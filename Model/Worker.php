<?php
namespace Api2cart\BridgeConnector\Model;

use Magento\Backend\Helper\Data;
use Magento\Framework\App\Filesystem\DirectoryList;

class Worker
{

    protected $_bridgeArchive;
    protected $_bridgeDir;
    protected $_bridgeFile;
    protected $_bridgeConfigFile;
    protected $_rootDir;
    protected $_filesystem;
    protected $_downloadBridgeUrl = 'https://api.api2cart.com/v1.0/bridge.download.file?whitelabel=true';
    protected $_extractEntities = [
      'bridge.php' => 'bridge2cart/bridge.php',
      'config.php' => 'bridge2cart/config.php',
      'index.php'  => 'bridge2cart/index.php'
    ];

    /**
     * Worker constructor.
     */
    public function __construct()
    {
        $varDirectory  = $this->_getFileSystem()->getPath(DirectoryList::VAR_DIR);
        $this->_rootDir = $this->_getFileSystem()->getPath(DirectoryList::ROOT);

        $this->_bridgeArchive     = $varDirectory . DIRECTORY_SEPARATOR . 'bridge.zip';
        $this->_bridgeDir         = $this->_rootDir . DIRECTORY_SEPARATOR . 'bridge2cart';
        $this->_bridgeFile        = $this->_bridgeDir . DIRECTORY_SEPARATOR . 'bridge.php';
        $this->_bridgeConfigFile  = $this->_bridgeDir . DIRECTORY_SEPARATOR . 'config.php';
    }

    /**
     * @return mixed
     */
    protected function _getFileSystem()
    {
        if (!$this->_filesystem) {
            $om = \Magento\Framework\App\ObjectManager::getInstance();
            $this->_filesystem = $om->get('\Magento\Framework\Filesystem\DirectoryList');
        }

        return $this->_filesystem;
    }

    /**
     * @param $url
     * @param int $timeout
     * @return bool|mixed|string
     */
    protected function _getContent($url, $timeout = 5)
    {
        if (in_array(ini_get('allow_url_fopen'), ['On', 'on', '1'])) {
            return file_get_contents($url);
        } elseif (function_exists('curl_init')) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
            $content = curl_exec($curl);
            curl_close($curl);
            return $content;
        } else {
            return false;
        }
    }

    /**
     * @return string
     */
    public function installBridge()
    {
        $installationStatus = $this->getBridgeStatus();

        if ($installationStatus === 3) {
            throw new \Exception('Connector already installed.');
        } elseif ($installationStatus > 0) {
            $this->removeBridge();
        }

        if (!is_writable($this->_rootDir)) {
            throw new \Exception($this->_rootDir . ' ' . 'is not writable');
        }

        mkdir($this->_bridgeDir, 0777);

        $res = file_put_contents($this->_bridgeArchive, $this->_getContent($this->_downloadBridgeUrl));
        if (!$res) {
            throw new \Exception('Can\'t write file to ' . $this->_bridgeArchive);
        }
        if (!$this->_extract()) {
            unlink($this->_bridgeArchive);
            throw new \Exception('Can\'t extract zip file ' . $this->_bridgeArchive);
        } else {
            unlink($this->_bridgeArchive);
        }

        $storeKey = $this->generateStoreKey();
        $this->updateStoreKey($storeKey);

        return $storeKey;
    }

    /**
     * @return bool
     */
    protected function _extract()
    {
        $zip = new \ZipArchive();
        $unzip = $zip->open($this->_bridgeArchive);

        if ($unzip === true) {
            foreach ($this->_extractEntities as $fileName => $filePath) {
                if ($fpr = $zip->getStream($filePath)) {
                    $fpw = fopen($this->_bridgeDir . DIRECTORY_SEPARATOR . $fileName, 'w');
                    while ($data = fread($fpr, 1024)) {
                        fwrite($fpw, $data);
                    }

                    fclose($fpw);
                    fclose($fpr);
                }
            }
        }

        return true;
    }

    /**
     * @return bool
     */
    public function removeBridge()
    {
        if ($this->getBridgeStatus() > 0) {
            $this->_deleteDir($this->_bridgeDir);
        }

        return true;
    }

    /**
     * @param $dirPath
     *
     * @return bool
     */
    private function _deleteDir($dirPath)
    {
        if (is_dir($dirPath)) {
            if (!is_writable($dirPath)) {
                throw new \Exception($dirPath . ' ' . 'is not writable');
            }

            $objects = scandir($dirPath);

            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (!is_writable($dirPath  . DIRECTORY_SEPARATOR . $object)) {
                        throw new \Exception($dirPath . DIRECTORY_SEPARATOR . $object . ' ' . 'is not writable');
                        return false;
                    }
                    if (filetype($dirPath . DIRECTORY_SEPARATOR . $object) == "dir") {
                        $this->_deleteDir($dirPath . DIRECTORY_SEPARATOR . $object);
                    } elseif (!unlink($dirPath . DIRECTORY_SEPARATOR . $object)) {
                        return false;
                    }
                }
            }

            reset($objects);

            if (!rmdir($dirPath)) {
                return false;
            }
        } else {
            return false;
        }

        return true;
    }

    /**
     * @return int
     */
    public function getBridgeStatus()
    {
        $status = 0;
        if (is_dir($this->_bridgeDir)) {
            $status++;
        }

        if (file_exists($this->_bridgeFile)) {
            $status++;
        }

        if (file_exists($this->_bridgeConfigFile)) {
            $status++;
        }

        return $status;
    }

    /**
     * @return bool
     */
    public function readStoreKey()
    {
        if (is_readable($this->_bridgeConfigFile)) {
            $filename = $this->_bridgeConfigFile;
            $handle = fopen($filename, "r");
            $context = fread($handle, filesize($this->_bridgeConfigFile));
            preg_match("/define\s*\(\s*[\"']M1_TOKEN[\"']\s*,\s*[\"'](.+)[\"']/", $context, $matches);

            return $matches[1];
        }

        return false;
    }

    /**
     * @param $token
     *
     * @return bool
     */
    public function updateStoreKey($token)
    {
        if (!is_writable($this->_bridgeConfigFile)) {
            throw new \Exception($this->_bridgeConfigFile . ' ' . 'is not writable');
            return false;
        }

        $config = fopen($this->_bridgeConfigFile, 'w');

        if (!$config) {
            return false;
        }

        $writed = fwrite($config, "<?php define('M1_TOKEN', '" . $token . "');");
        if ($writed === false) {
            return false;
        }

        fclose($config);

        return $this->readStoreKey();
    }

    /**
     * @return string
     */
    public function generateStoreKey()
    {
        $bytesLength = 16;

        if (function_exists('random_bytes')) { // available in PHP 7
            return bin2hex(random_bytes($bytesLength));
        }

        if (function_exists('openssl_random_pseudo_bytes')) {
            $bytes = openssl_random_pseudo_bytes($bytesLength);
            if ($bytes !== false) {
                return bin2hex($bytes);
            }
        }

        if (file_exists('/dev/urandom') && is_readable('/dev/urandom')) {
            $frandom = fopen('/dev/urandom', 'r');
            if ($frandom !== false) {
                return fread($frandom, $bytesLength);
            }
        }

        $rand = '';
        for ($i = 0; $i < $bytesLength; $i++) {
            $rand .= chr(random_int(0, 255));
        }

        return bin2hex($rand);
    }
}
