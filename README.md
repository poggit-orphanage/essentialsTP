# essentialsTP
EssentialsTP by ServerKart_Rod
-------
# Overview:
Welcome to EssentialsTP. 

# Offers:
This plugin Will provide you with the essentiality in essentialstp. you can warp. set spawn, tpa. Just like the original EssentialTP But updated:)
# Credits:
- Creator: ServerKart_Rod
- Updated by: poggit-orphanage to pm3
- Updated by: skyss0fly to pm4 and pm5

# Configuration:
to add permissions to a user you will need to use a permission manager:
Examples:
- [LunarRanks](https://poggit.pmmp.io/p/LunarRanks)
- [RankSystem](https://poggit.pmmp.io/p/RankSystem)
- [PurePerms](https://poggit.pmmp.io/p/PurePerms)

# Config:
```
---
plugin-name: essentalsTp+
sqlite-dbname: essentials_tp
tpa-here-cooldown: "30"
tp-home-cooldown: "5"
tp-warp-cooldown: "5"
tp-spawn-cooldown: "5"
MOTD: Welcome to this awsome plugin showcase was hell for the first day but hey lol
wild-MaxX: "300"
wild-MaxY: "300"
bed-sets-home: "true"
Lang_invalid_usage: 'INVALID USAGE:'
Lang_command_only_use_ingame: This command can only be used in the game.
Lang_no_permissions: '[EssentialsTP] No permission.'
Lang_teleport_home: Teleported you home.
Lang_teleport_spawn: Teleported you to spawn.
Lang_teleport_wild: Teleported you some where wild.
Lang_teleport_death: Teleported to last death cords.
Lang_no_spawn_set: No spawn point set for this world.... Using original world spawn.
Lang_teleport_spawn_original: Teleported you to worlds original spawn.
Lang_spawn_set: Spawn set.
Lang_your_homes: 'Your Homes:'
Lang_no_home_set: You have not yet set a home.
Lang_no_home_name: You do not have a home by that name.
Lang_home_set: Home set as
Lang_home_delete_1: 'Home named:'
Lang_home_delete_2: has been deleted.
Lang_no_teleport_self: You cant teleport to your self.
Lang_player_not_online: Player is not online.
Lang_sent_request_you: sent a request to teleport to you.
Lang_sent_request_them: sent a request to you, to teleport to them.
Lang_accept_request: to accept this request.
Lang_decline_request: to decline this request.
Lang_request_expire_1: This request is only good for
Lang_request_expire_2: Seconds
Lang_request_expire_3: before it expires.
Lang_type: Type
Lang_no_active_request: No active request exist.
Lang_warps: 'Warps:'
Lang_no_warps: This server has no warps.
Lang_no_warp_listed: There is no warp by that name listed.
Lang_warp_set: Warp set as
Lang_warp_to: You warped to
Lang_warp_delete_1: 'Warp named:'
Lang_warp_delete_2: has been deleted.
Lang_no_warps_name: No Warps matching that name for this server.
Land_chunk_not_loaded: Could not load chunk It's not safe to teleport.
Lang_action_cooldown: This action is on cooldown.
Lang_no_death: Die already please.
...
```

# Commands:
The following commands are:
```
- home:
   description: Teleports you home /home will list homes /home <homename> will teleport you.
   usage: "/home or /home <homename>"
   permission: essentialstp.command.home
- sethome:
   description: Sets your home, use names for multiple homes.
   usage: "/sethome <homename>"
   permission: essentialstp.command.sethome
- delhome:
   description: Delete your selected home.
   usage: "/delhome <homename>"
   permission: essentialstp.command.delhome
- back:
   description: Teleports you to your last known death location.
   usage: "/back"
   permission: essentialstp.command.back
- wild:
   description: Teleport to a random location in the world.
   usage: "/wild"
   permission: essentialstp.command.wild
- setspawn:
   description: Sets world spawn point.
   usage: "/setspawn"
   permission: essentialstp.command.setspawn
- spawn:
   description: Teleports to world spawn point.
   usage: "/spawn"
   permission: essentialstp.command.spawn
- warp:
   description: Warps you to a location /warp will list warps /warp <warpname> will teleport you.
   usage: "/warp or /warp <warpname>"
   permission: essentialstp.command.tpahere
- setwarp:
   description: Sets a warp location.
   usage: "/setwarp <warpname>"
   permission: essentialstp.command.setwarp
- delwarp:
   description: Delete a warp location.
   usage: "/delwarp <warpname>"
   permission: essentialstp.command.delwarp
- tpa:
   description: Send teleport request to player to teleport you to player.
   usage: "/tpa <player>"
   permission: essentialstp.command.tpa
- tpahere:
   description: Send teleport request to teleport player to you.
   usage: "/tpahere <player>"
   permission: essentialstp.command.tpahere
- tpaccept:
   description: Accept a teleport request.
   usage: "/tpaccept"
   permission: essentialstp.command.tpaccept
- tpdeny:
    description: decline all active teleport requests.
    usage: "/tpdeny"
    permission: essentialstp.command.tpdeny 

```
- *also shown in* `plugin.yml`
# Permissions:
```
- essentialstp.*:
   default: op
   description: "Allows all essentialsTP commands"
- essentialstp.command.*:
   description: "Allows player to use commands"
   default: op
- essentialstp.command.tpdeny:
      description: "Allows player to decline request"
      default: true
- essentialstp.command.tpaccept:
      description: "Allows player to accept request"
      default: true
- essentialstp.command.tpahere:
      description: "Allows player to teleport player to them"
      default: true
- essentialstp.command.tpa:
      description: "Allows player to teleport to another player"
      default: true
- essentialstp.command.delwarp:
      description: "Allows player to delete warps"
      default: op
- essentialstp.command.setwarp:
      description: "Allows player to set warps"
      default: op
- essentialstp.command.warp:
      description: "Allows player use warps"
      default: true
- essentialstp.command.setspawn:
      description: "Allows player to set spawn for world"
      default: op
- essentialstp.command.spawn:
      description: "Allows player use spawn command"
      default: true
- essentialstp.command.wild:
      description: "Allows player to teleport to a random location in world"
      default: true
- essentialstp.command.back:
      description: "Allows player go back to their last death location"
      default: true
- essentialstp.command.delhome:
      description: "Allows player to delete their homes"
      default: true
- essentialstp.command.sethome:
      description: "Allows player to set their homes"
      default: true
- essentialstp.command.home:
      description: "Allows player to use the home command"
      default: true
- essentialstp.command.bedsethome:
      description: "Allows player to set home with their bed"
      default: true
- essentialstp.command.sign.warp:
      description: "Allows player to use warp signs"
      default: true
- essentialstp.command.sign.wild:
      description: "Allows player to use wild signs"
      default: op
- essentialstp.command.sign.spawn:
      description: "Allows player to use spawn signs"
      default: true
- essentialstp.command.sign.warp.create:
      description: "Allows player to create warp signs"
      default: op
- essentialstp.command.sign.wild.create:
      description: "Allows player to create wild signs"
      default: op
- essentialstp.command.sign.spawn.create:
      description: "Allows player to create spawn signs"
      default: op
- essentialstp.command.sign.warp.break:
      description: "Allows player to break warp signs"
      default: op
- essentialstp.command.sign.wild.break:
      description: "Allows player to break wild signs"
      default: op
- essentialstp.command.sign.spawn.break:
      description: "Allows player to break spawn signs"
      default: op
```
- *also seen in* `plugin.yml`
