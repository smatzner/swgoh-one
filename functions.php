<?php

require 'vendor/autoload.php';

use Symfony\Component\DomCrawler\Crawler;

class Guild
{
    public $name;
    public $galacticPower;
    public $memberCount;
    public $avgGalacticPower;
    public $avgArenaRank;
    public $avgFleetArenaRank;

    private $html;
    private $crawler;

    public function __construct($guildID, $swgohRecruitmentID, $swgohRecruitmenttName = '')
    {
        $response = file_get_contents('http://api.swgoh.gg/guild-profile/' . $guildID);
        $guildData = json_decode($response);

        $this->name = $guildData->data->name;
        $this->galacticPower = number_format($guildData->data->galactic_power, 0, ',', '.');
        $this->memberCount = $guildData->data->member_count;
        $this->avgGalacticPower = number_format($guildData->data->avg_galactic_power, 0, ',', '.');
        $this->avgArenaRank = floor($guildData->data->avg_arena_rank);
        $this->avgFleetArenaRank = floor($guildData->data->avg_fleet_arena_rank);

        if(empty($swgohRecruitmenttName)){
            $this->html = file_get_contents('https://recruit.swgoh.gg/guild/'. $swgohRecruitmentID . '/' . $this->name);
        } else {
            $this->html = file_get_contents('https://recruit.swgoh.gg/guild/' . $swgohRecruitmentID . '/' . $swgohRecruitmenttName);
        }

        $this->crawler = new Crawler($this->html);
    }

    public function getRaidData()
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
            $raidData .= $raidScore . ' (' . $raidName . ')';
            if ($raidName != array_key_last($raidDataArray)) {
                $raidData .= ' // ';
            }   
        }

        return $raidData;
    }

        public function getTBData()
        {
            $TBProgressElement = $this->crawler->filter('h2:contains("TB Progress") + div');

            $TBScores = [];
            $TBProgressElement->children()->filter('div.card-body')->each(function ($TBScore) use (&$TBScores) {
                array_push($TBScores, preg_replace('/[a-zA-Z]/', '', $TBScore->text()));
            });

            $TBNames = [];
            $TBProgressElement->children()->filter('img')->each(function ($TBName) use (&$TBNames) {
                if($TBName->attr('alt') != 'star'){
                    array_push($TBNames, strtoupper($TBName->attr('alt')));
                }
            });

            $TBDataArray = array_combine($TBNames,$TBScores);
            
            $TBData = '';
            foreach ($TBDataArray as $TBName => $TBScore) {
                $TBData .= $TBScore . ' (' . $TBName . ')';
                if ($TBName != array_key_last($TBDataArray)) {
                    $TBData .= ' // ';
                }
            }

            return $TBData;
        }
    }
