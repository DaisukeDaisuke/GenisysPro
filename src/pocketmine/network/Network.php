<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

/**
 * Network-related classes
 */

namespace pocketmine\network;

use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\AddHangingEntityPacket;
use pocketmine\network\protocol\AddItemEntityPacket;
use pocketmine\network\protocol\AddItemPacket;
use pocketmine\network\protocol\AddPaintingPacket;
use pocketmine\network\protocol\AddPlayerPacket;
use pocketmine\network\protocol\AdventureSettingsPacket;
use pocketmine\network\protocol\AnimatePacket;
use pocketmine\network\protocol\AvailableCommandsPacket;
use pocketmine\network\protocol\BatchPacket;
use pocketmine\network\protocol\BlockEntityDataPacket;
use pocketmine\network\protocol\BlockEventPacket;
use pocketmine\network\protocol\BossEventPacket;
use pocketmine\network\protocol\BlockPickRequestPacket;
use pocketmine\network\protocol\ChangeDimensionPacket;
use pocketmine\network\protocol\ChunkRadiusUpdatedPacket;
use pocketmine\network\protocol\ClientboundMapItemDataPacket;
use pocketmine\network\protocol\ClientToServerHandshakePacket;
use pocketmine\network\protocol\CommandBlockUpdatePacket;
use pocketmine\network\protocol\CommandStepPacket;
use pocketmine\network\protocol\ContainerClosePacket;
use pocketmine\network\protocol\ContainerOpenPacket;
use pocketmine\network\protocol\ContainerSetContentPacket;
use pocketmine\network\protocol\ContainerSetDataPacket;
use pocketmine\network\protocol\ContainerSetSlotPacket;
use pocketmine\network\protocol\CraftingDataPacket;
use pocketmine\network\protocol\CraftingEventPacket;
use pocketmine\network\protocol\CameraPacket;
use pocketmine\network\protocol\DataPacket;
use pocketmine\network\protocol\DisconnectPacket;
use pocketmine\network\protocol\DropItemPacket;
use pocketmine\network\protocol\EntityEventPacket;
use pocketmine\network\protocol\ExplodePacket;
use pocketmine\network\protocol\FullChunkDataPacket;
use pocketmine\network\protocol\HurtArmorPacket;
use pocketmine\network\protocol\Info;
use pocketmine\network\protocol\InteractPacket;
use pocketmine\network\protocol\InventoryActionPacket;
use pocketmine\network\protocol\ItemFrameDropItemPacket;
use pocketmine\network\protocol\LevelEventPacket;
use pocketmine\network\protocol\LevelSoundEventPacket;
use pocketmine\network\protocol\LoginPacket;
use pocketmine\network\protocol\MapInfoRequestPacket;
use pocketmine\network\protocol\MobArmorEquipmentPacket;
use pocketmine\network\protocol\MobEquipmentPacket;
use pocketmine\network\protocol\MoveEntityPacket;
use pocketmine\network\protocol\MovePlayerPacket;
use pocketmine\network\protocol\PlaySoundPacket;
use pocketmine\network\protocol\PlayStatusPacket;
use pocketmine\network\protocol\PlayerActionPacket;
use pocketmine\network\protocol\EntityFallPacket;
use pocketmine\network\protocol\PlayerInputPacket;
use pocketmine\network\protocol\PlayerListPacket;
use pocketmine\network\protocol\RemoveBlockPacket;
use pocketmine\network\protocol\RemoveEntityPacket;
use pocketmine\network\protocol\ReplaceItemInSlotPacket;
use pocketmine\network\protocol\RequestChunkRadiusPacket;
use pocketmine\network\protocol\ResourcePackChunkDataPacket;
use pocketmine\network\protocol\ResourcePackChunkRequestPacket;
use pocketmine\network\protocol\ResourcePackClientResponsePacket;
use pocketmine\network\protocol\ResourcePackDataInfoPacket;
use pocketmine\network\protocol\ResourcePacksInfoPacket;
use pocketmine\network\protocol\ResourcePackStackPacket;
use pocketmine\network\protocol\RespawnPacket;
use pocketmine\network\protocol\RiderJumpPacket;
use pocketmine\network\protocol\SetCommandsEnabledPacket;
use pocketmine\network\protocol\SetDifficultyPacket;
use pocketmine\network\protocol\SetEntityDataPacket;
use pocketmine\network\protocol\SetEntityLinkPacket;
use pocketmine\network\protocol\SetEntityMotionPacket;
use pocketmine\network\protocol\SetHealthPacket;
use pocketmine\network\protocol\SetPlayerGameTypePacket;
use pocketmine\network\protocol\SetSpawnPositionPacket;
use pocketmine\network\protocol\SetTimePacket;
use pocketmine\network\protocol\SetTitlePacket;
use pocketmine\network\protocol\ServerToClientHandshakePacket;
use pocketmine\network\protocol\ShowCreditsPacket;
use pocketmine\network\protocol\SpawnExperienceOrbPacket;
use pocketmine\network\protocol\StartGamePacket;
use pocketmine\network\protocol\StopSoundPacket;
use pocketmine\network\protocol\TakeItemEntityPacket;
use pocketmine\network\protocol\TextPacket;
use pocketmine\network\protocol\TransferPacket;
use pocketmine\network\protocol\UpdateBlockPacket;
use pocketmine\network\protocol\UpdateTradePacket;
use pocketmine\network\protocol\UseItemPacket;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\BinaryStream;
use pocketmine\utils\MainLogger;

class Network {

	public static $BATCH_THRESHOLD = 512;

	/** @var \SplFixedArray */
	private $packetPool;

	/** @var Server */
	private $server;

	/** @var SourceInterface[] */
	private $interfaces = [];

	/** @var AdvancedSourceInterface[] */
	private $advancedInterfaces = [];

	private $upload = 0;
	private $download = 0;

	private $name;

	/**
	 * Network constructor.
	 *
	 * @param Server $server
	 */
	public function __construct(Server $server){

		$this->registerPackets();

		$this->server = $server;
	}

	/**
	 * @param $upload
	 * @param $download
	 */
	public function addStatistics($upload, $download){
		$this->upload += $upload;
		$this->download += $download;
	}

	/**
	 * @return int
	 */
	public function getUpload(){
		return $this->upload;
	}

	/**
	 * @return int
	 */
	public function getDownload(){
		return $this->download;
	}

	public function resetStatistics(){
		$this->upload = 0;
		$this->download = 0;
	}

	/**
	 * @return SourceInterface[]
	 */
	public function getInterfaces(){
		return $this->interfaces;
	}

	public function processInterfaces(){
		foreach($this->interfaces as $interface){
			try{
				$interface->process();
			}catch(\Throwable $e){
				$logger = $this->server->getLogger();
				if(\pocketmine\DEBUG > 1){
					if($logger instanceof MainLogger){
						$logger->logException($e);
					}
				}

				$interface->emergencyShutdown();
				$this->unregisterInterface($interface);
				$logger->critical($this->server->getLanguage()->translateString("pocketmine.server.networkError", [get_class($interface), $e->getMessage()]));
			}
		}
	}

	/**
	 * @param SourceInterface $interface
	 */
	public function registerInterface(SourceInterface $interface){
		$this->interfaces[$hash = spl_object_hash($interface)] = $interface;
		if($interface instanceof AdvancedSourceInterface){
			$this->advancedInterfaces[$hash] = $interface;
			$interface->setNetwork($this);
		}
		$interface->setName($this->name);
	}

	/**
	 * @param SourceInterface $interface
	 */
	public function unregisterInterface(SourceInterface $interface){
		unset($this->interfaces[$hash = spl_object_hash($interface)],
			$this->advancedInterfaces[$hash]);
	}

	/**
	 * Sets the server name shown on each interface Query
	 *
	 * @param string $name
	 */
	public function setName($name){
		$this->name = (string) $name;
		foreach($this->interfaces as $interface){
			$interface->setName($this->name);
		}
	}

	public function getName(){
		return $this->name;
	}

	public function updateName(){
		foreach($this->interfaces as $interface){
			$interface->setName($this->name);
		}
	}

	/**
	 * @param int        $id 0-255
	 * @param DataPacket $class
	 */
	public function registerPacket($id, $class){
		$this->packetPool[$id] = new $class;
	}

	/**
	 * @return Server
	 */
	public function getServer(){
		return $this->server;
	}

	/**
	 * @param BatchPacket $packet
	 * @param Player      $p
	 */
	public function processBatch(BatchPacket $packet, Player $p){
		try{
			if(strlen($packet->payload) === 0){
				//prevent zlib_decode errors for incorrectly-decoded packets
				throw new \InvalidArgumentException("BatchPacket payload is empty or packet decode error");
			}

			$str = zlib_decode($packet->payload, 1024 * 1024 * 64); //Max 64MB
			$len = strlen($str);

			if($len === 0){
				throw new \InvalidStateException("Decoded BatchPacket payload is empty");
			}

			$stream = new BinaryStream($str);

			while($stream->offset < $len){
				$buf = $stream->getString();
				if(($pk = $this->getPacket(ord($buf{0}))) !== null){
					if($pk::NETWORK_ID === 0xfe){
						throw new \InvalidStateException("Invalid BatchPacket inside BatchPacket");
					}

					$pk->setBuffer($buf, 1);

					$pk->decode();
					assert($pk->feof(), "Still " . strlen(substr($pk->buffer, $pk->offset)) . " bytes unread in " . get_class($pk));
					$p->handleDataPacket($pk);
				}
			}
		}catch(\Throwable $e){
			if(\pocketmine\DEBUG > 1){
				$logger = $this->server->getLogger();
				if($logger instanceof MainLogger){
					$logger->debug("BatchPacket " . " 0x" . bin2hex($packet->payload));
					$logger->logException($e);
				}
			}
		}
	}

	/**
	 * @param $id
	 *
	 * @return DataPacket
	 */
	public function getPacket($id){
		/** @var DataPacket $class */
		$class = $this->packetPool[$id];
		if($class !== null){
			return clone $class;
		}
		return null;
	}


	/**
	 * @param string $address
	 * @param int    $port
	 * @param string $payload
	 */
	public function sendPacket($address, $port, $payload){
		foreach($this->advancedInterfaces as $interface){
			$interface->sendRawPacket($address, $port, $payload);
		}
	}

	/**
	 * Blocks an IP address from the main interface. Setting timeout to -1 will block it forever
	 *
	 * @param string $address
	 * @param int    $timeout
	 */
	public function blockAddress($address, $timeout = 300){
		foreach($this->advancedInterfaces as $interface){
			$interface->blockAddress($address, $timeout);
		}
	}

	/**
	 * Unblocks an IP address from the main interface.
	 *
	 * @param string $address
	 */
	public function unblockAddress($address){
		foreach($this->advancedInterfaces as $interface){
			$interface->unblockAddress($address);
		}
	}

	private function registerPackets(){
		$this->packetPool = new \SplFixedArray(256);

		$this->registerPacket(Info::ADD_ENTITY_PACKET, AddEntityPacket::class);
		$this->registerPacket(Info::ADD_HANGING_ENTITY_PACKET, AddHangingEntityPacket::class);
		$this->registerPacket(Info::ADD_ITEM_ENTITY_PACKET, AddItemEntityPacket::class);
		$this->registerPacket(Info::ADD_ITEM_PACKET, AddItemPacket::class);
		$this->registerPacket(Info::ADD_PAINTING_PACKET, AddPaintingPacket::class);
		$this->registerPacket(Info::ADD_PLAYER_PACKET, AddPlayerPacket::class);
		$this->registerPacket(Info::ADVENTURE_SETTINGS_PACKET, AdventureSettingsPacket::class);
		$this->registerPacket(Info::ANIMATE_PACKET, AnimatePacket::class);
		$this->registerPacket(Info::AVAILABLE_COMMANDS_PACKET, AvailableCommandsPacket::class);
		$this->registerPacket(0xfe, BatchPacket::class);
		$this->registerPacket(Info::BLOCK_ENTITY_DATA_PACKET, BlockEntityDataPacket::class);
		$this->registerPacket(Info::BLOCK_EVENT_PACKET, BlockEventPacket::class);
		$this->registerPacket(Info::BOSS_EVENT_PACKET, BossEventPacket::class);
		$this->registerPacket(Info::CAMERA_PACKET, CameraPacket::class);
		$this->registerPacket(Info::CHANGE_DIMENSION_PACKET, ChangeDimensionPacket::class);
		$this->registerPacket(Info::CHUNK_RADIUS_UPDATED_PACKET, ChunkRadiusUpdatedPacket::class);
		$this->registerPacket(Info::CLIENTBOUND_MAP_ITEM_DATA_PACKET, ClientboundMapItemDataPacket::class);
		$this->registerPacket(Info::CLIENT_TO_SERVER_HANDSHAKE_PACKET, ClientToServerHandshakePacket::class);
		$this->registerPacket(Info::COMMAND_STEP_PACKET, CommandStepPacket::class);
		$this->registerPacket(Info::CONTAINER_CLOSE_PACKET, ContainerClosePacket::class);
		$this->registerPacket(Info::CONTAINER_OPEN_PACKET, ContainerOpenPacket::class);
		$this->registerPacket(Info::CONTAINER_SET_CONTENT_PACKET, ContainerSetContentPacket::class);
		$this->registerPacket(Info::CONTAINER_SET_DATA_PACKET, ContainerSetDataPacket::class);
		$this->registerPacket(Info::CONTAINER_SET_SLOT_PACKET, ContainerSetSlotPacket::class);
		$this->registerPacket(Info::CRAFTING_DATA_PACKET, CraftingDataPacket::class);
		$this->registerPacket(Info::CRAFTING_EVENT_PACKET, CraftingEventPacket::class);
		$this->registerPacket(Info::DISCONNECT_PACKET, DisconnectPacket::class);
		$this->registerPacket(Info::DROP_ITEM_PACKET, DropItemPacket::class);
		$this->registerPacket(Info::ENTITY_EVENT_PACKET, EntityEventPacket::class);
		$this->registerPacket(Info::EXPLODE_PACKET, ExplodePacket::class);
		$this->registerPacket(Info::FULL_CHUNK_DATA_PACKET, FullChunkDataPacket::class);
		$this->registerPacket(Info::HURT_ARMOR_PACKET, HurtArmorPacket::class);
		$this->registerPacket(Info::INTERACT_PACKET, InteractPacket::class);
		$this->registerPacket(Info::INVENTORY_ACTION_PACKET, InventoryActionPacket::class);
		$this->registerPacket(Info::ITEM_FRAME_DROP_ITEM_PACKET, ItemFrameDropItemPacket::class);
		$this->registerPacket(Info::LEVEL_EVENT_PACKET, LevelEventPacket::class);
		$this->registerPacket(Info::LEVEL_SOUND_EVENT_PACKET, LevelSoundEventPacket::class);
		$this->registerPacket(Info::LOGIN_PACKET, LoginPacket::class);
		$this->registerPacket(Info::MAP_INFO_REQUEST_PACKET, MapInfoRequestPacket::class);
		$this->registerPacket(Info::MOB_ARMOR_EQUIPMENT_PACKET, MobArmorEquipmentPacket::class);
		$this->registerPacket(Info::MOB_EQUIPMENT_PACKET, MobEquipmentPacket::class);
		$this->registerPacket(Info::MOVE_ENTITY_PACKET, MoveEntityPacket::class);
		$this->registerPacket(Info::MOVE_PLAYER_PACKET, MovePlayerPacket::class);
		$this->registerPacket(Info::ENTITY_FALL_PACKET, EntityFallPacket::class);
		$this->registerPacket(Info::PLAYER_ACTION_PACKET, PlayerActionPacket::class);
		$this->registerPacket(Info::PLAYER_INPUT_PACKET, PlayerInputPacket::class);
		$this->registerPacket(Info::PLAYER_LIST_PACKET, PlayerListPacket::class);
		$this->registerPacket(Info::PLAY_STATUS_PACKET, PlayStatusPacket::class);
		$this->registerPacket(Info::REMOVE_BLOCK_PACKET, RemoveBlockPacket::class);
		$this->registerPacket(Info::REMOVE_ENTITY_PACKET, RemoveEntityPacket::class);
		$this->registerPacket(Info::REPLACE_ITEM_IN_SLOT_PACKET, ReplaceItemInSlotPacket::class);
		$this->registerPacket(Info::REQUEST_CHUNK_RADIUS_PACKET, RequestChunkRadiusPacket::class);
		$this->registerPacket(Info::RESOURCE_PACK_CHUNK_REQUEST_PACKET, ResourcePackChunkRequestPacket::class);
		$this->registerPacket(Info::RESOURCE_PACK_CHUNK_DATA_PACKET, ResourcePackChunkDataPacket::class);
		$this->registerPacket(Info::RESOURCE_PACK_CLIENT_RESPONSE_PACKET, ResourcePackClientResponsePacket::class);
		$this->registerPacket(Info::RESOURCE_PACK_DATA_INFO_PACKET, ResourcePackDataInfoPacket::class);
		$this->registerPacket(Info::RESOURCE_PACKS_INFO_PACKET, ResourcePacksInfoPacket::class);
		$this->registerPacket(Info::RESOURCE_PACK_STACK_PACKET, ResourcePackStackPacket::class);
		$this->registerPacket(Info::RESPAWN_PACKET, RespawnPacket::class);
		$this->registerPacket(Info::RIDER_JUMP_PACKET, RiderJumpPacket::class);
		$this->registerPacket(Info::SHOW_CREDITS_PACKET, ShowCreditsPacket::class);
		$this->registerPacket(Info::SERVER_TO_CLIENT_HANDSHAKE_PACKET, ServerToClientHandshakePacket::class);
		$this->registerPacket(Info::SET_COMMANDS_ENABLED_PACKET, SetCommandsEnabledPacket::class);
		$this->registerPacket(Info::SET_DIFFICULTY_PACKET, SetDifficultyPacket::class);
		$this->registerPacket(Info::SET_ENTITY_DATA_PACKET, SetEntityDataPacket::class);
		$this->registerPacket(Info::SET_ENTITY_LINK_PACKET, SetEntityLinkPacket::class);
		$this->registerPacket(Info::SET_ENTITY_MOTION_PACKET, SetEntityMotionPacket::class);
		$this->registerPacket(Info::SET_HEALTH_PACKET, SetHealthPacket::class);
		$this->registerPacket(Info::SET_PLAYER_GAME_TYPE_PACKET, SetPlayerGameTypePacket::class);
		$this->registerPacket(Info::SET_SPAWN_POSITION_PACKET, SetSpawnPositionPacket::class);
		$this->registerPacket(Info::SET_TIME_PACKET, SetTimePacket::class);
		$this->registerPacket(Info::SPAWN_EXPERIENCE_ORB_PACKET, SpawnExperienceOrbPacket::class);
		$this->registerPacket(Info::START_GAME_PACKET, StartGamePacket::class);
		$this->registerPacket(Info::TAKE_ITEM_ENTITY_PACKET, TakeItemEntityPacket::class);
		$this->registerPacket(Info::TEXT_PACKET, TextPacket::class);
		$this->registerPacket(Info::TRANSFER_PACKET, TransferPacket::class);
		$this->registerPacket(Info::UPDATE_BLOCK_PACKET, UpdateBlockPacket::class);
		$this->registerPacket(Info::UPDATE_TRADE_PACKET, UpdateTradePacket::class);
		$this->registerPacket(Info::USE_ITEM_PACKET, UseItemPacket::class);
		$this->registerPacket(Info::BLOCK_PICK_REQUEST_PACKET, BlockPickRequestPacket::class);
		$this->registerPacket(Info::COMMAND_BLOCK_UPDATE_PACKET, CommandBlockUpdatePacket::class);
		$this->registerPacket(Info::PLAY_SOUND_PACKET, PlaySoundPacket::class);
		$this->registerPacket(Info::SET_TITLE_PACKET, SetTitlePacket::class);
		$this->registerPacket(Info::STOP_SOUND_PACKET, StopSoundPacket::class);
	}
}
