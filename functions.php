<?php

require 'vendor/autoload.php';

use Symfony\Component\DomCrawler\Crawler;

class Guild
{
    private $name;
    private $galacticPower;
    private $memberCount;
    private $avgGalacticPower;
    private $avgArenaRank;
    private $avgFleetArenaRank;

    private $html;
    private $crawler;

    public function __construct($guildID, $swgohRecruitmentID, $swgohRecruitmentName = '')
    {
        $response = file_get_contents('http://api.swgoh.gg/guild-profile/' . $guildID);
        $guildData = json_decode($response);

        $this->name = $guildData->data->name;
        $this->galacticPower = number_format($guildData->data->galactic_power, 0, ',', '.');
        $this->memberCount = $guildData->data->member_count;
        $this->avgGalacticPower = number_format($guildData->data->avg_galactic_power, 0, ',', '.');
        $this->avgArenaRank = floor($guildData->data->avg_arena_rank);
        $this->avgFleetArenaRank = floor($guildData->data->avg_fleet_arena_rank);

        if (empty($swgohRecruitmentName)) {
            $this->html = file_get_contents('https://recruit.swgoh.gg/guild/' . $swgohRecruitmentID . '/' . $this->name);
        } else {
            $this->html = file_get_contents('https://recruit.swgoh.gg/guild/' . $swgohRecruitmentID . '/' . $swgohRecruitmentName);
        }

        $this->crawler = new Crawler($this->html);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getGalacticPower(): string
    {
        return $this->galacticPower;
    }

    public function getMemberCount()
    {
        return $this->memberCount;
    }

    public function getAvgGalacticPower(): string
    {
        return $this->avgGalacticPower;
    }

    public function getAvgArenaRank()
    {
        return $this->avgArenaRank;
    }

    public function getAvgFleetArenaRank()
    {
        return $this->avgFleetArenaRank;
    }

    public function getRaidData(): string
    {
        $raidProgressElement = $this->crawler->filter('h2:contains("Raid Progress") + div');

        $raidScores = [];
        $raidProgressElement->children()->filter('span.badge')->each(function ($raidScore) use (&$raidScores) {
            array_push($raidScores, $raidScore->text());
        });

        $raidNames = [];
        $raidProgressElement->children()->filter('img')->each(function ($raidName) use (&$raidNames) {
            array_push($raidNames, $raidName->attr('alt'));
        });

        $raidDataArray = array_combine($raidNames, $raidScores);

        $raidData = '';
        foreach ($raidDataArray as $raidName => $raidScore) {
            $raidData .= $raidScore . ' [' . $raidName . ']';
            if ($raidName != array_key_last($raidDataArray)) {
                $raidData .= ' // ';
            }
        }

        return $raidData;
    }

    public function getTbData(): string
    {
        $tbProgressElement = $this->crawler->filter('h2:contains("TB Progress") + div');
        $starImg = '<img src="https://www.swgoh.one/wp-content/uploads/2023/08/icon-star.png">';

        $tbScores = [];
        $tbProgressElement->children()->filter('div.card-body')->each(function ($TBScore) use (&$tbScores) {
            array_push($tbScores, preg_replace('/[a-zA-Z]/', '', $TBScore->text()));
        });

        $tbNames = [];
        $tbProgressElement->children()->filter('img')->each(function ($TBName) use (&$tbNames) {
            if ($TBName->attr('alt') != 'star') {
                array_push($tbNames, strtoupper($TBName->attr('alt')));
            }
        });

        $tbDataArray = array_combine($tbNames, $tbScores);

        $tbData = '';
        foreach ($tbDataArray as $tbName => $tbScore) {
            $tbData .= $tbScore . ' ' .$starImg. ' [' . $tbName . ']';
            if ($tbName != array_key_last($tbDataArray)) {
                $tbData .= ' // ';
            }
        }

        return $tbData;
    }

    public function getTwData() : string
    {
        $badgeElements = $this->crawler->filter('.rounded-pill');

        $badgeElements->each(function ($badgeElement) use (&$twBadge) {
            if (preg_match('/.* TW$/', $badgeElement->text())) {
                $twBadge = preg_replace('/\s+TW$/','',$badgeElement->text());
            }
        });
        return $twBadge;
    }
}
