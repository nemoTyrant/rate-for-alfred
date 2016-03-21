<?php
require_once 'workflows.php';

class Rate
{
    private $url = 'https://sp0.baidu.com/8aQDcjqpAAV3otqbppnN2DJv/api.php?query=<query>&resource_id=6017&format=json';
    private $file;
    private $countryNames;

    public function __construct()
    {
        $this->file = __DIR__ . '/names.txt';
        $this->loadCountryNames();
    }

    // public function getRate($amount, $src, $dst = 'cny')
    public function getRate($query)
    {
        $query = explode(' ', $query);
        if (count($query) < 2) {
            $this->notify('请输入货币');
        } elseif (count($query) == 2) {
            if (empty($query[1]) || !array_key_exists(strtoupper($query[1]), $this->countryNames)) {
                $this->notify('请输入货币');
            }
        } elseif (count($query) == 3) {
            // 如果第二个参数是错的
            if (!array_key_exists(strtoupper($query[1]), $this->countryNames)) {
                $this->notify('来源参数错误，请重新输入');
            }

            if (!empty($query[2]) && !array_key_exists(strtoupper($query[2]), $this->countryNames)) {
                $this->notify('请输入目标货币');
            }
        } else {
            $this->notify('请按正确格式输入', '例如： 10 USD CNY');
        }
        $rs = $this->rate($query[0], $query[1], empty($query[2]) ? 'cny' : $query[2]);
        if ($rs) {
            $this->notify('结果:' . $rs);
        } else {
            $this->notify('请按正确格式输入', '例如： 10 USD CNY');
        }
    }

    private function notify($title, $subtitle = '')
    {
        $workflows = new Workflows;
        $workflows->result(1, // uid 必须从 1 开始。alfred 会把 uid 为 1 的第一个显示
            '',
            $title,
            $subtitle,
            'icon.png',
            false);
        echo $workflows->toxml();
        exit;
    }

    // convert rate
    private function rate($amount, $src, $dst = 'cny')
    {
        if (!array_key_exists(strtoupper($src), $this->countryNames) || !array_key_exists(strtoupper($dst), $this->countryNames)) {
            return false;
        }
        $query = $amount . $this->countryNames[strtoupper($src)] . '等于多少' . $this->countryNames[strtoupper($dst)];
        $json = json_decode($this->get($query), true);
        $targetAmount = $json['data'][0]['number2'];
        return $targetAmount;
    }

    private function loadCountryNames()
    {
        $countryNames = @file_get_contents($this->file);
        if ($countryNames) {
            $json = unserialize($countryNames);
            if ($json['updateTime'] > time() - 86400) {
                $this->countryNames = $json['data'];
                return;
            }
        }
        $this->updateCountryNames();
    }

    private function updateCountryNames()
    {
        $json = json_decode($this->get('1美元等于多少人民币'), true);
        // parse
        $names = [];
        foreach ($json['data'][0]['tab'] as $moneys) {
            foreach ($moneys['moneys']['money'] as $money) {
                $names[$money['code']] = $money['name'];
            }
        }
        ksort($names);
        $data = [
            'updateTime' => time(),
            'data' => $names,
        ];
        file_put_contents($this->file, serialize($data));
        $this->countryNames = $names;
    }

    private function get($query)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, str_replace('<query>', $query, $this->url));
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);

        $content = curl_exec($ch);
        $info = curl_getinfo($ch);
        if ($info['http_code'] != 200) {
            exit('wrong http code');
        }
        if (strpos($info['content_type'], 'gbk')) {
            $content = mb_convert_encoding($content, 'utf8', 'gbk');
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $content;
    }
}
