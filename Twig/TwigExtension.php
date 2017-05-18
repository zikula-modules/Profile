<?php

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - http://zikula.org/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\ProfileModule\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Zikula\Common\Translator\TranslatorInterface;

/**
 * Twig extension class.
 */
class TwigExtension extends \Twig_Extension
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * TwigExtension constructor.
     *
     * @param TranslatorInterface $translator
     * @param RequestStack $requestStack
     */
    public function __construct(
        TranslatorInterface $translator,
        RequestStack $requestStack
    ) {
        $this->translator = $translator;
        $this->requestStack = $requestStack;
    }

    /**
     * Returns a list of custom Twig functions.
     *
     * @return array
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('zikulaprofilemodule_gravatar', [$this, 'getGravatarImage'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * The zikulaprofilemodule_gravatar function returns either a Gravatar URL
     * or a complete image tag for a specified email address.
     *
     * Example:
     *     {{ zikulaprofilemodule_gravatar(email = 'user@example.com') }}
     *
     * @see http://en.gravatar.com/site/implement/images/php/
     *
     * @param string $email The email address
     * @param int    $size  Size in pixels; defaults to 80px [ 1 - 2048 ]
     * @param string $d     Default imageset to use [ 404 | mm | identicon | monsterid | wavatar ]
     * @param string $r     Maximum rating (inclusive) [ g | pg | r | x ]
     * @param bool   $f     Force default image; defaults to FALSE
     * @param bool   $image TRUE to return a complete IMG tag, FALSE for just the URL
     *
     * @return string Either just a URL or a complete image tag
     */
    public function getGravatarImage($email = 'user@example.com', $size = 80, $d = 'mm', $r = 'g', $f = false, $image = true)
    {
        $result = $this->requestStack->getCurrentRequest()->isSecure() ? 'https://secure.gravatar.com/avatar/' : 'http://www.gravatar.com/avatar/';
        $result .= md5(strtolower(trim($email))).'.jpg';
        $result .= '?s='.$size.'&amp;d='.$d.'&amp;r='.$r.($f ? '&amp;f='.$f : '');

        if ($image) {
            $result = '<img src="'.$result.'" class="img-thumbnail" alt="'.$this->translator->__('Avatar').'" />';
        }

        return $result;
    }
}
