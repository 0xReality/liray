<?php

namespace core;

use pocketmine\block\Block;
use pocketmine\block\BlockIdentifier;
use pocketmine\event\block\BaseBlockChangeEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;


class Main extends PluginBase implements Listener
{
    private const LC = 5;
    private const FBF = 3;

    private array $NR = [
        129, // Emerald Ore
        27,  // Powered Rail
        56,  // Diamond Ore

    ];

    private array $OU = [
        118, // Cauldron
        116, // Enchanting Table
        61,  // Furnace
        154, // Hopper
        152, // Redstone Block
        21,  // Lapis Lazuli Ore
        58,  // Crafting Table
        117, // Brewing Stand
    ];

    private array $OD = [
        116, // Enchanting Table
        130, // Ender Chest
        7,   // Bedrock
        120, // End Portal Frame
        145, // Anvil
        130, // Ender Chest
        2,   // Grass Block
        148, // Grindstone

    ];


    public function onEnable(): void {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }


    public function onJoin(PlayerJoinEvent $event): void
    {
        $player = $event->getPlayer();
        $player->sendMessage("Hello " . $player->getName());
    }

    public function onTick(PlayerMoveEvent $event): void
    {
        $player = $event->getPlayer();
        $world = $player->getWorld();
        $c = $player->getPosition()->floor(16)->asVector3();
        $s = mt_rand(0, self::FBF - 1);
        $i = 0;

        for ($y = 0; $y <= 7; $y++) {
            for ($x = $c->x - self::LC; $x <= $c->x + self::LC; $x++) {
                for ($z = $c->z - self::LC; $z <= $c->z + self::LC; $z++) {
                    if ($i % self::FBF === $s) {
                        $pos = new Vector3($x * 16 + mt_rand(0, 15), $y * 16 + mt_rand(0, 15), $z * 16 + mt_rand(0, 15));
                        $block = $world->getBlock($pos);
                        $id = 0;

                        switch ($block->getTypeId()) {
                            default:
                                break;
                            case 0:
                                $y++;
                                break;
                            case 418:
                            case 129:
                            case 2:
                            case 12:
                            case 13:
                            case 16:
                            case 80:
                                if (!$this->hasTransparentFaces($world, $pos)) {
                                $replacementId = $this->getReplacementBlock($y);
                                $this->sendReplacementBlockPacket([$player], $pos, $replacementId);
                            }
                                break;
                        }
                    }
                    $i++;
                }
            }
        }
    }


    public function onStartDestroyBlock(BlockBreakEvent $event): void
    {
        $block = $event->getBlock();
        if (!$block->isTransparent()) {
            $this->reloadBlockListeners($block->getPosition());
        }
    }


    private function reloadBlockListeners(Vector3 $pos): void
    {
        $players = $this->getServer()->getOnlinePlayers();
        $blocks = $this->getBlocksAllFaces($pos);

        foreach ($players as $player){
            foreach ($blocks as $b) {
                $world = $player->getWorld();
                $block = $world->getBlock($b);
                switch ($block->getTypeId()) {
                    case 1:
                        $this->sendReplacementBlockPacket($players, $b->asVector3(), 1);
                        break;
                    case 3:
                        $this->sendReplacementBlockPacket($players, $b->asVector3(), 3);
                        break;
                    case 2:
                        $this->sendReplacementBlockPacket($players, $b->asVector3(), 2);
                        break;
                    case 12:
                        $this->sendReplacementBlockPacket($players, $b->asVector3(), 12);
                        break;
                    case 13:
                        $this->sendReplacementBlockPacket($players, $b->asVector3(), 13);
                        break;
                }
            }
        }
    }

    private function hasTransparentFaces($level, $pos): bool
    {
        $faces = [
            $level->getBlock($pos->add(1, 0, 0)),
            $level->getBlock($pos->subtract(1, 0, 0)),
            $level->getBlock($pos->add(0, 1, 0)),
            $level->getBlock($pos->subtract(0, 1, 0)),
            $level->getBlock($pos->add(0, 0, 1)),
            $level->getBlock($pos->subtract(0, 0, 1)),
        ];

        foreach ($faces as $face) {
            if ($face->isTransparent()) {
                return true;
            }
        }
        return false;
    }

    private function getBlocksAllFaces($pos): array
    {
        $blocks = [];
        foreach ([-1, 1] as $a) {
            $blocks = array_merge($blocks, [
                $pos->add($a, 0, 0),
                $pos->add(0, $a, 0),
                $pos->add(0, 0, $a),
            ]);
        }
        return $blocks;
    }

    private function sendReplacementBlockPacket(array $players, Vector3 $pos, int $id): void
    {
        $pk = new UpdateBlockPacket();
        $pk->x = $pos->x;
        $pk->y = $pos->y;
        $pk->z = $pos->z;
        $pk->blockRuntimeId = $id;
        $pk->flags = UpdateBlockPacket::FLAG_NONE;
        foreach ($players as $player) {
            if ($player instanceof Player) {
                $player->getNetworkSession()->sendDataPacket($pk);
            }
        }
    }

    private function getReplacementBlock(int $y): int
    {
        $blocks = ($y > 0 ? $this->OU : $this->OD);
        return $blocks[array_rand($blocks)];
    }
}
