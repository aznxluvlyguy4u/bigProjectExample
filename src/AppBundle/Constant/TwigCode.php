<?php

namespace AppBundle\Constant;


class TwigCode
{
    const STAR_BLACK_UNICODE = '&#9733;';
    const STAR_WHITE_UNICODE = '&#9734;';
    const STAR_BLACK = '<svg class="icon icon-star-full"><use xlink:href="#icon-star-full"></use></svg>';
    const STAR_WHITE = '<svg class="icon icon-star-empty"><use xlink:href="#icon-star-empty"></use></svg>';
    const STAR_HALF = '<svg class="icon icon-star-half"><use xlink:href="#icon-star-half"></use></svg>';
    const AUTO_ESCAPE_START = '{% autoescape %}';
    const AUTO_ESCAPE_END = '{% endautoescape %}';
}