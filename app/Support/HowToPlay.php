<?php

namespace App\Support;

/**
 * Short "How to play" notes for the game page, keyed by game slug.
 * Goal and strategy tips only; keys are not listed. Written from each game's
 * description and genre, not from playing it, so check them against the game.
 */
class HowToPlay
{
    public const TIPS = [
        'chopper' => ['Fly through the caverns and rescue everyone waiting below.', ['Small, gentle corrections beat big swerves in tight gaps.', 'Look ahead: the next obstacle matters more than the one you are passing.']],
        'blockworld' => ['Gather blocks and resources by day, build a shelter, and survive the night.', ['Build a simple shelter before your first nightfall.', 'Light keeps the dark at bay, so place it generously.']],
        'towers' => ['Stop every wave of enemies before they reach the end of the path.', ['Place towers where the path bends so they fire for longer.', 'Upgrade a few strong towers rather than spreading thin.']],
        'dungeon' => ['Explore the dungeon, defeat monsters and find the treasure.', ['Pick up items as you go; some fights need them.', 'Retreat to heal before a tough battle, not during it.']],
        'space-invaders' => ['Shoot down the advancing aliens before they reach the ground.', ['Hide behind the shields and pick off the lowest rows first.', 'The invaders speed up as their numbers drop.']],
        'missile-command' => ['Protect your cities by destroying incoming missiles in mid-air.', ['Aim slightly ahead of a missile so your blast meets it.', 'One well-placed explosion can stop several missiles.']],
        'asteroids' => ['Blast every asteroid without colliding with the debris.', ['Large rocks split into faster small ones, so clear them carefully.', 'Keep your speed low; drifting is hard to stop.']],
        'pacman' => ['Eat every dot in the maze while avoiding the ghosts.', ['Save the big power pellets for when ghosts are close.', 'Use the side tunnels to escape a chase.']],
        'tetris' => ['Fit the falling blocks together to clear full lines.', ['Keep the stack flat and leave one column open for long pieces.', 'Clearing several lines at once scores more.']],
        'snake' => ['Eat food to grow, without hitting a wall or your own tail.', ['Move in wide loops to keep space open as you grow.', 'Plan your route to the food before you turn.']],
        'pong' => ['Return the ball past your opponent\'s paddle to score.', ['Hitting with the paddle edge sends the ball at a sharper angle.', 'Return to the centre after each hit.']],
        'frogger' => ['Guide the frog across the road and river to a safe home.', ['Wait for a gap rather than rushing through traffic.', 'Ride the logs and turtles, but watch for turtles that dive.']],
        'bomberman' => ['Drop bombs to clear walls and defeat enemies in the maze.', ['Always know your escape route before you place a bomb.', 'Power-ups hidden in walls give you bigger blasts and more bombs.']],
        'minesweeper' => ['Uncover every safe square without hitting a mine.', ['A number shows how many mines touch that square.', 'Flag the mines you are sure of to work out the rest.']],
        'reversi' => ['Finish with more discs of your colour on the board.', ['Corners can never be flipped back, so they are worth a lot.', 'Avoid the squares next to corners early on.']],
        'candy' => ['Swap neighbouring candies to line up three or more of the same kind.', ['There is no rush: take your time to spot the best swap.', 'Matches near the bottom can set off bonus chains.']],
        'space-huggers' => ['Run, jump and shoot your way through each level.', ['Keep moving; standing still makes you an easy target.', 'Explore for weapons and power-ups along the way.']],
        'miniciv' => ['Grow a small civilisation by gathering resources and expanding.', ['Balance food and production so growth does not stall.', 'Expand early while land is free.']],
        'dwarfs' => ['Lead the dwarfs as they dig, gather and make their way home.', ['Gather supplies before heading into the unknown.', 'Keep the party together.']],
        'woolfie' => ['Sneak through the castle, find the key and reach the exit unseen.', ['Watch the guards\' patterns before you move.', 'Patience wins: wait for the gap, then go.']],
        'lawyer-memory' => ['Turn over two cards at a time and find all the matching pairs.', ['Remember where each card was, even when it does not match.', 'There is no time limit, so play at your own pace.']],
        'doctor-memory' => ['Turn over two cards at a time and find all the matching pairs.', ['Work through the board in order to remember positions.', 'There is no time limit, so play at your own pace.']],
        'note-quest' => ['Answer ten questions per quiz about notes, keys and sounds.', ['Begin with treble clef notes, then mix in the bass clef.', 'For the listening quizzes, sing the notes quietly to compare them.']],
    ];

    /** @return array{0:string,1:array<int,string>}|null */
    public static function for(string $slug): ?array
    {
        if (! isset(self::TIPS[$slug])) {
            return null;
        }
        $key = "howto.{$slug}";

        return ! app()->isLocale('en') && \Illuminate\Support\Facades\Lang::hasForLocale($key) ? __($key) : self::TIPS[$slug];
    }
}
