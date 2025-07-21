<?php

namespace Drupal\portotheme_style_switcher\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class MzCartController.
 */
class PortoThemeController extends ControllerBase
{

    /**
     * Remove.
     *
     * @return string
     *   Return Hello string.
     */
    public function index()
    {
        $css = \Drupal::request()->request->get('getCSS');
        if ($css) {
            \Drupal::configFactory()->getEditable('portotheme_style_switcher.theme_style')
                ->set('css', $this->minify($css))
                ->save();
                \Drupal::messenger()->addMessage('Theme Style is Updated');
                \Drupal::service('cache.render')->invalidateAll();
                $helper = \Drupal::service('drupal.helper')->helper;
                $helper->redirectTo('');
                
        }
        $build = [
            '#theme' => 'portotheme_style_switcher',
        ];
        return $build;
    }
    private function minify($css)
    {
      $css = preg_replace('/\/\*((?!\*\/).)*\*\//', '', $css); // negative look ahead
      $css = preg_replace('/\s{2,}/', ' ', $css);
      $css = preg_replace('/\s*([:;{}])\s*/', '$1', $css);
      $css = preg_replace('/;}/', '}', $css);
      return $css; 
    }
}
