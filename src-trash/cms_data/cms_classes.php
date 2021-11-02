<?php
class CmsVersionDetector {
    private $root_path;
    private $versions;
    private $types;

    public function __construct($root_path = '.') {
        $this->root_path = $root_path;
        $this->versions = array();
        $this->types = array();

        $version = '';

        $dir_list = $this->getDirList($root_path);
        $dir_list[] = $root_path;

        foreach ($dir_list as $dir) {
            if ($this->checkBitrix($dir, $version)) {
                $this->addCms(CMS_BITRIX, $version);
            }

            if ($this->checkWordpress($dir, $version)) {
                $this->addCms(CMS_WORDPRESS, $version);
            }

            if ($this->checkJoomla($dir, $version)) {
                $this->addCms(CMS_JOOMLA, $version);
            }

            if ($this->checkDle($dir, $version)) {
                $this->addCms(CMS_DLE, $version);
            }

            if ($this->checkIpb($dir, $version)) {
                $this->addCms(CMS_IPB, $version);
            }

            if ($this->checkWebAsyst($dir, $version)) {
                $this->addCms(CMS_WEBASYST, $version);
            }

            if ($this->checkOsCommerce($dir, $version)) {
                $this->addCms(CMS_OSCOMMERCE, $version);
            }

            if ($this->checkDrupal($dir, $version)) {
                $this->addCms(CMS_DRUPAL, $version);
            }

            if ($this->checkMODX($dir, $version)) {
                $this->addCms(CMS_MODX, $version);
            }

            if ($this->checkInstantCms($dir, $version)) {
                $this->addCms(CMS_INSTANT_CMS, $version);
            }

            if ($this->checkPhpBb($dir, $version)) {
                $this->addCms(CMS_PHPBB, $version);
            }

            if ($this->checkVBulletin($dir, $version)) {
                $this->addCms(CMS_VBULLETIN, $version);
            }

            if ($this->checkPhpShopScript($dir, $version)) {
                $this->addCms(CMS_SHOP_SCRIPT, $version);
            }

        }
    }

    function getDirList($target): array
    {
        $remove = array('.', '..');
        $directories = array_diff(scandir($target), $remove);

        $res = array();

        foreach($directories as $value)
        {
            if(is_dir($target . '/' . $value))
            {
                $res[] = $target . '/' . $value;
            }
        }

        return $res;
    }

    function isCms($name, $version): bool
    {
        for ($i = 0; $i < count($this->types); $i++) {
            if ((strpos($this->types[$i], $name) !== false)
                &&
                (strpos($this->versions[$i], $version) !== false)) {
                return true;
            }
        }

        return false;
    }

//    function getCmsList(): array
//    {
//        return $this->types;
//    }
//
//    function getCmsVersions(): array
//    {
//        return $this->versions;
//    }

    function getCmsNumber(): int
    {
        return count($this->types);
    }

    function getCmsName($index = 0) {
        return $this->types[$index];
    }

    function getCmsVersion($index = 0) {
        return $this->versions[$index];
    }

    private function addCms($type, $version) {
        $this->types[] = $type;
        $this->versions[] = $version;
    }

    private function checkBitrix($dir, &$version): bool
    {
        $version = CMS_VERSION_UNDEFINED;
        $res = false;

        if (file_exists($dir .'/bitrix')) {
            $res = true;

            $tmp_content = @file_get_contents($this->root_path .'/bitrix/modules/main/classes/general/version.php');
            if (preg_match('|define\("SM_VERSION","(.+?)"\)|smi', $tmp_content, $tmp_ver)) {
                $version = $tmp_ver[1];
            }

        }

        return $res;
    }

    private function checkWordpress($dir, &$version): bool
    {
        $version = CMS_VERSION_UNDEFINED;
        $res = false;

        if (file_exists($dir .'/wp-admin')) {
            $res = true;

            $tmp_content = @file_get_contents($dir .'/wp-includes/version.php');
            if (preg_match('|\$wp_version\s*=\s*\'(.+?)\'|smi', $tmp_content, $tmp_ver)) {
                $version = $tmp_ver[1];
            }
        }

        return $res;
    }

    private function checkJoomla($dir, &$version): bool
    {
        $version = CMS_VERSION_UNDEFINED;
        $res = false;

        if (file_exists($dir .'/libraries/joomla')) {
            $res = true;

            // for 1.5.x
            $tmp_content = @file_get_contents($dir .'/libraries/joomla/version.php');
            if (preg_match('|var\s+\$RELEASE\s*=\s*\'(.+?)\'|smi', $tmp_content, $tmp_ver)) {
                $version = $tmp_ver[1];

                if (preg_match('|var\s+\$DEV_LEVEL\s*=\s*\'(.+?)\'|smi', $tmp_content, $tmp_ver)) {
                    $version .= '.' . $tmp_ver[1];
                }
            }

            // for 1.7.x
            $tmp_content = @file_get_contents($dir .'/includes/version.php');
            if (preg_match('|public\s+\$RELEASE\s*=\s*\'(.+?)\'|smi', $tmp_content, $tmp_ver)) {
                $version = $tmp_ver[1];

                if (preg_match('|public\s+\$DEV_LEVEL\s*=\s*\'(.+?)\'|smi', $tmp_content, $tmp_ver)) {
                    $version .= '.' . $tmp_ver[1];
                }
            }

            // for 2.5.x and 3.x
            $tmp_content = @file_get_contents($dir .'/libraries/cms/version/version.php');
            if (preg_match('|public\s+\$RELEASE\s*=\s*\'(.+?)\'|smi', $tmp_content, $tmp_ver)) {
                $version = $tmp_ver[1];

                if (preg_match('|public\s+\$DEV_LEVEL\s*=\s*\'(.+?)\'|smi', $tmp_content, $tmp_ver)) {
                    $version .= '.' . $tmp_ver[1];
                }
            }

        }

        return $res;
    }

    private function checkDle($dir, &$version): bool
    {
        $version = CMS_VERSION_UNDEFINED;
        $res = false;

        if (file_exists($dir .'/engine/engine.php')) {
            $res = true;

            $tmp_content = @file_get_contents($dir . '/engine/data/config.php');
            if (preg_match('|\'version_id\'\s*=>\s*"(.+?)"|smi', $tmp_content, $tmp_ver)) {
                $version = $tmp_ver[1];
            }

            $tmp_content = @file_get_contents($dir . '/install.php');
            if (preg_match('|\'version_id\'\s*=>\s*"(.+?)"|smi', $tmp_content, $tmp_ver)) {
                $version = $tmp_ver[1];
            }

        }

        return $res;
    }

    private function checkIpb($dir, &$version): bool
    {
        $version = CMS_VERSION_UNDEFINED;
        $res = false;

        if (file_exists($dir . '/ips_kernel')) {
            $res = true;

            $tmp_content = @file_get_contents($dir . '/ips_kernel/class_xml.php');
            if (preg_match('|IP.Board\s+v([0-9.]+)|si', $tmp_content, $tmp_ver)) {
                $version = $tmp_ver[1];
            }

        }

        return $res;
    }

    private function checkWebAsyst($dir, &$version): bool
    {
        $version = CMS_VERSION_UNDEFINED;
        $res = false;

        if (file_exists($dir . '/wbs/installer')) {
            $res = true;

            $tmp_content = @file_get_contents($dir . '/license.txt');
            if (preg_match('|v([0-9.]+)|si', $tmp_content, $tmp_ver)) {
                $version = $tmp_ver[1];
            }

        }

        return $res;
    }

    private function checkOsCommerce($dir, &$version): bool
    {
        $version = CMS_VERSION_UNDEFINED;
        $res = false;

        if (file_exists($dir . '/includes/version.php')) {
            $res = true;

            $tmp_content = @file_get_contents($dir . '/includes/version.php');
            if (preg_match('|([0-9.]+)|smi', $tmp_content, $tmp_ver)) {
                $version = $tmp_ver[1];
            }

        }

        return $res;
    }

    private function checkDrupal($dir, &$version): bool
    {
        $version = CMS_VERSION_UNDEFINED;
        $res = false;

        if (file_exists($dir . '/sites/all')) {
            $res = true;

            $tmp_content = @file_get_contents($dir . '/CHANGELOG.txt');
            if (preg_match('|Drupal\s+([0-9.]+)|smi', $tmp_content, $tmp_ver)) {
                $version = $tmp_ver[1];
            }

        }

        return $res;
    }

    private function checkMODX($dir, &$version): bool
    {
        $version = CMS_VERSION_UNDEFINED;
        $res = false;

        if (file_exists($dir . '/manager/assets')) {
            $res = true;

            // no way to pick up version
        }

        return $res;
    }

    private function checkInstantCms($dir, &$version): bool
    {
        $version = CMS_VERSION_UNDEFINED;
        $res = false;

        if (file_exists($dir . '/plugins/p_usertab')) {
            $res = true;

            $tmp_content = @file_get_contents($dir . '/index.php');
            if (preg_match('|InstantCMS\s+v([0-9.]+)|smi', $tmp_content, $tmp_ver)) {
                $version = $tmp_ver[1];
            }

        }

        return $res;
    }

    private function checkPhpBb($dir, &$version): bool
    {
        $version = CMS_VERSION_UNDEFINED;
        $res = false;

        if (file_exists($dir . '/includes/acp')) {
            $res = true;

            $tmp_content = @file_get_contents($dir . '/config.php');
            if (preg_match('|phpBB\s+([0-9.x]+)|smi', $tmp_content, $tmp_ver)) {
                $version = $tmp_ver[1];
            }

        }

        return $res;
    }

    private function checkVBulletin($dir, &$version): bool
    {
        $version = CMS_VERSION_UNDEFINED;
        $res = false;

        if (file_exists($dir . '/core/admincp')) {
            $res = true;

            $tmp_content = @file_get_contents($dir . '/core/api.php');
            if (preg_match('|vBulletin\s+([0-9.x]+)|smi', $tmp_content, $tmp_ver)) {
                $version = $tmp_ver[1];
            }

        }

        return $res;
    }

    private function checkPhpShopScript($dir, &$version): bool
    {
        $version = CMS_VERSION_UNDEFINED;
        $res = false;

        if (file_exists($dir . '/install/consts.php')) {
            $res = true;

            $tmp_content = @file_get_contents($dir . '/install/consts.php');
            if (preg_match('|STRING_VERSION\',\s*\'(.+?)\'|smi', $tmp_content, $tmp_ver)) {
                $version = $tmp_ver[1];
            }

        }

        return $res;
    }
}