<?php

namespace core;


use pocketmine\block\BlockTypeIds;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;


class Main extends PluginBase implements Listener
{
    private const LC = 5;
    private const FBF = 3;

    private array $NR = [
        BlockTypeIds::EMERALD_ORE,
        BlockTypeIds::POWERED_RAIL,
        BlockTypeIds::DIAMOND_ORE,
        BlockTypeIds::IRON_ORE,
    ];

    private array $OU = [
        BlockTypeIds::CAULDRON,
        BlockTypeIds::ENCHANTING_TABLE,
        BlockTypeIds::FURNACE,
        BlockTypeIds::HOPPER,
        BlockTypeIds::REDSTONE,
        BlockTypeIds::LAPIS_LAZULI_ORE,
        BlockTypeIds::CRAFTING_TABLE,
        BlockTypeIds::BREWING_STAND,
    ];

    private array $OD = [
        BlockTypeIds::ENCHANTING_TABLE,
        BlockTypeIds::ENDER_CHEST,
        BlockTypeIds::BEDROCK,
        BlockTypeIds::END_PORTAL_FRAME,
        BlockTypeIds::ANVIL,
        BlockTypeIds::ENDER_CHEST,
        BlockTypeIds::GRASS,
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
                    case BlockTypeIds::STONE:
                        $this->sendReplacementBlockPacket($players, $b->asVector3(), BlockTypeIds::STONE);
                        break;
                    case BlockTypeIds::DIRT:
                        $this->sendReplacementBlockPacket($players, $b->asVector3(), BlockTypeIds::DIRT);
                        break;
                    case BlockTypeIds::GRASS:
                        $this->sendReplacementBlockPacket($players, $b->asVector3(), BlockTypeIds::GRASS);
                        break;
                    case BlockTypeIds::SAND:
                        $this->sendReplacementBlockPacket($players, $b->asVector3(), BlockTypeIds::SAND);
                        break;
                    case BlockTypeIds::GRAVEL:
                        $this->sendReplacementBlockPacket($players, $b->asVector3(), BlockTypeIds::GRAVEL);
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
        $blockPosition = new BlockPosition((int) $pos->x, (int) $pos->y, (int) $pos->z);
        $pk = UpdateBlockPacket::create($blockPosition, $id, UpdateBlockPacket::FLAG_NONE, 0);
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
