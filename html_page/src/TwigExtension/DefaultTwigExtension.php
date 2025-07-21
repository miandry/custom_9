<?php

namespace Drupal\html_page\TwigExtension;


use Twig\TwigFunction;
use Twig\Extension\AbstractExtension;
/**
 * Class DefaultTwigExtension.
 */
class DefaultTwigExtension extends AbstractExtension
{

    /**
     * {@inheritdoc}
     */
    public function getTokenParsers()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeVisitors()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getTests()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('include_html', ['Drupal\html_page\TwigExtension\DefaultTwigExtension', 'include_html_twig']),
       ];
    }

    




    public static function include_html_twig($id, $variables = [])
    {
        $service = \Drupal::service('html_page.manager');
        $data = $service->loadData($id);
        if (!empty($data) 
        && isset($data["content"]) 
        && isset($data["content"]["value"])
        && isset($data["id"])
        ) {
            $loader = new \Twig\Loader\ArrayLoader([
                'Temp_file.html' => $data["content"]["value"],
            ]);
            $twig = new \Twig\Environment($loader);
            return $twig->render('Temp_file.html', $variables);
        } else {
            $message = 'HTML Page not exist   ' . $id . ' not exist';
            \Drupal::logger("html_page")->error($message);
            return "";
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getOperators()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'templating.twig.extension';
    }

}
