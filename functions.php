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

    private $crawler;

    /**
     * @param string $guildID - The Guild ID used in https://swgoh.gg/g/<b>&lt;Guild ID&gt;</>/)
     */
    public function __construct(string $guildID)
    {
        $response = file_get_contents('http://api.swgoh.gg/guild-profile/' . $guildID);

        if(!$response){
            throw new Exception();
        }

        $guildData = json_decode($response);

        if(!$guildData){
            throw new Exception();
        }

        $this->name = $guildData->data->name;
        $this->galacticPower = number_format($guildData->data->galactic_power, 0, ',', '.');
        $this->memberCount = $guildData->data->member_count;
        $this->avgGalacticPower = number_format($guildData->data->avg_galactic_power, 0, ',', '.');
        $this->avgArenaRank = floor($guildData->data->avg_arena_rank);
        $this->avgFleetArenaRank = floor($guildData->data->avg_fleet_arena_rank);
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


    /**
     * @param int $swgohRecruitmentID - ID used by https://recruit.swgoh.gg/guild/<b>&lt;ID&gt;</b>/&lt;guild name&gt;
     * @param string $swgohRecruitmentName - Guild name used by https.//recruit.swgoh.gg/guild/&lt;ID&gt;/<b>&lt;guild name&gt;</b> - only needed in case guild name has more than one words or special characters
     * @return void
     */
    public function setCrawler(int $swgohRecruitmentID, string $swgohRecruitmentName = ''): void
    {
        if (empty($swgohRecruitmentName)) {
            $html = file_get_contents('https://recruit.swgoh.gg/guild/' . $swgohRecruitmentID . '/' . $this->name);
        } else {
            $html = file_get_contents('https://recruit.swgoh.gg/guild/' . $swgohRecruitmentID . '/' . $swgohRecruitmentName);
        }

        $this->crawler = new Crawler($html);
    }


    public function getRaidData($default): string
    {
        try {
            $raidProgressElement = $this->crawler->filter('h2:contains("Raid Progress") + div');

            $raidScores = [];
            $raidProgressElement->children()->filter('span.badge')->each(function ($raidScore) use (&$raidScores) {
                array_push($raidScores, preg_replace('/,/', '.', $raidScore->text()));
            });

            $raidNames = [];
            $raidProgressElement->children()->filter('img')->each(function ($raidName) use (&$raidNames) {
                array_push($raidNames, $raidName->attr('alt'));
            });

            if (empty($raidNames) || empty($raidScores)) {
                throw new Exception();
            }

            $raidNames = array_unique($raidNames);

            $raidDataArray = array_combine($raidNames, $raidScores);

            $raidData = '';
            foreach ($raidDataArray as $raidName => $raidScore) {
                $raidData .= $raidScore . ' [' . $raidName . ']';
                if ($raidName !== array_key_last($raidDataArray)) {
                    $raidData .= ' // ';
                }
            }

            return $raidData;
        } catch (Exception $e) {
            return $default;
        }
    }

    public function getTbData($default): string
    {
        try {
            $tbProgressElement = $this->crawler->filter('h2:contains("TB Progress") + div');
            $starImg = '<img class="starIcon" src="https://www.swgoh.one/wp-content/uploads/2023/08/icon-star.png">';

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
            $tbNames = array_unique($tbNames);

            $tbDataArray = array_combine($tbNames, $tbScores);

            $tbData = '';
            foreach ($tbDataArray as $tbName => $tbScore) {
                $tbData .= $tbScore . ' ' . $starImg . ' [' . $tbName . ']';
                if ($tbName != array_key_last($tbDataArray)) {
                    $tbData .= ' // ';
                }
            }

            return $tbData;
        } catch (Exception $e) {
            return $default;
        }
    }

    public function getTwData($default): string
    {
        try {
            $badgeElements = $this->crawler->filter('.rounded-pill');
            $badgeElements->each(function ($badgeElement) use (&$twBadge) {
                if (preg_match('/.* TW$/', $badgeElement->text())) {
                    $twBadge = preg_replace('/\s+TW$/', '', $badgeElement->text());
                }
            });
            if(!$twBadge){
                throw new Exception();
            }
            return $twBadge;

        } catch (Exception $e) {
            return $default;
        }

    }
}
