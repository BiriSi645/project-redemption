<?php

if (! function_exists('render_mentions')) {
    function render_mentions(string $text): string
    {
        $pattern = '/(?<![\p{L}\p{N}_.-])@([\p{L}\p{N}_](?:[\p{L}\p{N}_.-]{1,98}[\p{L}\p{N}_]))/u';
        preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE);
        $result = '';
        $offset = 0;

        foreach ($matches[0] ?? [] as $index => $match) {
            [$mention, $position] = $match;
            $username = $matches[1][$index][0];
            $result .= esc(substr($text, $offset, $position - $offset));
            $url = site_url('users/u') . '?username=' . rawurlencode($username);
            $result .= '<a class="mention-link" href="' . esc($url, 'attr') . '">' . esc($mention) . '</a>';
            $offset = $position + strlen($mention);
        }

        return $result . esc(substr($text, $offset));
    }
}
