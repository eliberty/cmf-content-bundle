<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2017 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Bundle\ContentBundle\Controller;

use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

/**
 * This controller renders the content object with a template defined on the route.
 */
class ContentController
{
    /**
     * @var EngineInterface|null
     */
    protected $templating;

    /**
     * @var Environment|null
     */
    protected $twig;

    /**
     * @var string
     */
    protected $defaultTemplate;

    /**
     * @var ViewHandlerInterface
     */
    protected $viewHandler;

    /**
     * Instantiate the content controller.
     *
     * @param EngineInterface      $templating      The templating instance to
     *                                              render the template
     * @param string               $defaultTemplate Default template to use in
     *                                              case none is specified by
     *                                              the request
     * @param ViewHandlerInterface $viewHandler     Optional view handler
     *                                              instance
     */
    public function __construct(EngineInterface $templating = null, $defaultTemplate = null, ViewHandlerInterface $viewHandler = null, Environment $twig = null)
    {
        if (is_null($templating) && is_null($twig)) {
            throw new \InvalidArgumentException('One of Templating or Twig must be specified');
        }
        $this->templating = $templating;
        $this->defaultTemplate = $defaultTemplate;
        $this->viewHandler = $viewHandler;
        $this->twig = $twig;
    }

    /**
     * Render the provided content.
     *
     * When using the publish workflow, enable the publish_workflow.request_listener
     * of the core bundle to have the contentDocument as well as the route
     * checked for being published.
     * We don't need an explicit check in this method.
     *
     * @param Request $request
     * @param object  $contentDocument
     * @param string  $template        Symfony path of the template to render
     *                                 the content document. If omitted, the
     *                                 default template is used
     *
     * @return Response
     */
    public function indexAction(Request $request, $contentDocument, $template = null)
    {
        $contentTemplate = $template ?: $this->defaultTemplate;

        $contentTemplate = str_replace(
            ['{_format}', '{_locale}'],
            [$request->getRequestFormat(), $request->getLocale()],
            $contentTemplate
        );

        $params = $this->getParams($request, $contentDocument);

        return $this->renderResponse($contentTemplate, $params);
    }

    protected function renderResponse($contentTemplate, $params)
    {
        if ($this->viewHandler) {
            if (1 === (is_countable($params) ? count($params) : 0)) {
                $templateVar = key($params);
                $params = reset($params);
            }
            $view = $this->getView($params);
            if (isset($templateVar)) {
                $view->setTemplateVar($templateVar);
            }
            $view->setTemplate($contentTemplate);

            return $this->viewHandler->handle($view);
        }

        if (is_null($this->templating)) {
            $response = new Response();
            $response->setContent($this->twig->render($contentTemplate, $params));
        } else {
            $response = $this->templating->renderResponse($contentTemplate, $params);
        }

        return $response;
    }

    /**
     * Prepare the REST View to render the response in the correct format.
     *
     * @param array $params
     *
     * @return View
     */
    protected function getView($params)
    {
        return new View($params);
    }

    /**
     * Determine the parameters for rendering the template.
     *
     * This is mainly meant as a possible extension point in a custom
     * controller.
     *
     * @param Request $request
     * @param object  $contentDocument
     *
     * @return array
     */
    protected function getParams(Request $request, $contentDocument)
    {
        return [
            'cmfMainContent' => $contentDocument,
        ];
    }
}
