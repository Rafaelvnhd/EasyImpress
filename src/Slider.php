<?php
/*
* This file is part of the Orbitale EasyImpress package.
*
* (c) Alexandre Rock Ancelet <alex@orbitale.io>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

class Slider implements \Countable
{

    /** @var Slide[] */
    protected $slides;

    /** @var array */
    protected $values = array();

    /** @var array */
    protected $config = array();

    /** @var string */
    protected $name;

    protected $defaultImpressData = array(
        'x'        => null,
        'y'        => null,
        'z'        => null,
        'rotate'   => null,
        'rotate-x' => null,
        'rotate-y' => null,
        'rotate-z' => null,
    );

    /**
     * @param string $name
     * @param array  $slides
     *
     * @return $this
     */
    public static function create($name, array $slides)
    {
        $self = new static($name, $slides);
        return $self;
    }

    public function __call($method, $args)
    {
        if (strpos($method, 'get') === 0) {
            $method = lcfirst(preg_replace('~^get~', '', $method));
            if (isset($this->values[$method])) {
                return $this->values[$method];
            }
        }
        return null;
    }

    function __construct($name, array $slides)
    {
        $this->name = $name;

        if (!$slides) {
            throw new \InvalidArgumentException('Slider is empty.');
        }

        $this->computeConfigDatas($slides);
    }

    /**
     * @param array $slides
     */
    protected function computeConfigDatas(array $slides = array())
    {
        if (!isset($slides['config'])) { $slides['config'] = array(); }
        if (!isset($slides['config']['data'])) { $slides['config']['data'] = array(); }
        if (!isset($slides['slides'])) { $slides['slides'] = array(); }

        if (!count($slides['slides'])) {
            throw new \InvalidArgumentException('You must define at least one slide.');
        }

        $this->slides = $slides;

        $this->slides['config']['data'] = array_merge(array(
            'transition-duration' => 1000,
            'name'                => $this->name,
        ), $this->slides['config']['data']);

        $this->slides['config']['data'] = array_merge($this->defaultImpressData, isset($this->slides['config']['data']) ? $this->slides['config']['data'] : array());

        if (!isset($this->slides['config']['increments'])) {
            $this->slides['config']['increments'] = array();
        }

        foreach ($this->defaultImpressData as $key => $v) {
            if (!isset($this->slides['config']['increments'][$key])) {
                $this->slides['config']['increments'][$key] = array();
            }
            $this->slides['config']['increments'][$key] = array_merge(array(
                'current' => 0,
                'base' => null,
                'i' => null,
            ), $this->slides['config']['increments'][$key]);
        }

        if (!isset($this->slides['config']['attr']['class'])) {
            $this->slides['config']['attr']['class'] = 'impress_slides_container';
        }
        if (strpos($this->slides['config']['attr']['class'], 'impress_slides_container') === false) {
            $this->slides['config']['attr']['class'] = 'impress_slides_container '.$this->slides['config']['attr']['class'];
        }

        $this->slides['config']['inactive_opacity'] = isset($this->slides['config']['inactive_opacity']) ? (float) $this->slides['config']['inactive_opacity'] : 1;

        $this->slides['config']['attr']['class'] .= ' impress_slide_'.$this->name;
        $this->slides['config']['attr']['class'] = trim($this->slides['config']['attr']['class']);

        $this->computeSlidesDatas();

        $this->config = $this->slides['config'];
        $this->values = $this->slides;
        $this->slides = $this->slides['slides'];
    }

    /**
     * @return array
     */
    protected function computeSlidesDatas()
    {
        if (!$this->slides || !$this->slides['slides']) {
            throw new \InvalidArgumentException('No slides to compute');
        }

        $calculateIncrements = $this->slides['config']['increments'];

        foreach ($this->slides['slides'] as $k => $slide) {
            $slide = array_merge(array(
                'id'              => $k,
                'attr'            => array(),
                'reset'           => array(),
                'view'            => false,
                'image'           => false,
                'wrapWithTag'     => '',
                'text'            => '',
                'credits'         => '',
            ), $slide ?: array());

            $slide['reset'] = array_merge(array_fill_keys(array_keys($this->defaultImpressData), false), $slide['reset']);

            $slide['attr']['class'] = trim('step '.(isset($slide['attr']['class']) ? $slide['attr']['class'] : ''));

            if (!$slide['text'] && $k !== 'overview') {
                $slide['text'] = 'slides.'.$this->slides['config']['data']['name'].'.'.$k;
            }

            $slideDatas = array_merge($this->defaultImpressData, isset($slide['data']) && count((array)$slide['data']) ? $slide['data'] : $this->defaultImpressData);

            foreach ($calculateIncrements as $incType => $incData) {
                if (null !== $incData['base'] && isset($slide['reset'][$incType]) && $slide['reset'][$incType] === true ) {
                    $calculateIncrements[$incType]['current'] = $incData['base'];
                }
                if (null === $slideDatas[$incType]) {
                    $slideDatas[$incType] = $calculateIncrements[$incType]['current'];
                }
                if (null !== $incData['base'] && null !== $incData['i']) {
                    $calculateIncrements[$incType]['current'] += $incData['i'];
                }
            }

            $slideDatas = array_merge(array_fill_keys(array_keys($this->defaultImpressData), 0), array_filter($slideDatas));

            $slide['data'] = $slideDatas;

            $this->slides['slides'][$k] = new Slide($slide, $this);
        }
    }

    public function getName()
    {
        return $this->name;
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function toArray()
    {
        return $this->values;
    }

    public function getSlides()
    {
        return $this->slides;
    }

    public function getSlide($id)
    {
        if (!isset($this->slides[$id])) {
            return new \Exception('Slide "'.$id.'" does not exist in current slider.');
        }
        return $this->slides[$id];
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->slides);
    }
}
