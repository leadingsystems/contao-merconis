<?php

namespace LeadingSystems\MerconisBundle\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Configures the bundle.
 *
 * @author Leading Systems GmbH
 */
class ShowMailController
{
	/**
	 * Database connection.
	 *
	 * @var Connection
	 */
	private $connection;

	/**
	 * Template engine.
	 *
	 * @var \Twig\Environment
	 */
	private $engine;

	/**
	 * ShowMailController constructor.
	 *
	 * @param Connection $connection
	 * @param \Twig\Environment $engine
	 */
	public function __construct(Connection $connection, \Twig\Environment $engine)
	{
		$this->connection = $connection;
		$this->engine = $engine;
	}

	/**
	 * Render the html mail body.
	 *
	 * @param int $messageId The message id.
	 * @param Request $request
	 *
	 * @return \Symfony\Component\HttpFoundation\Response
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function htmlBodyAction($messageId, Request $request)
	{
		$htmlBody = 'Message could not be loaded';

		if ($messageId) {
			$statement = $this->connection
				->executeQuery('SELECT bodyHTML FROM tl_ls_shop_messages_sent WHERE id=:id', ['id' => $messageId]);

			if ($statement->rowCount() === 1) {
				$htmlBody = $statement->fetchColumn();
				$htmlBody = preg_replace('/(<\/title>)/', '$1<base href="'. $request->getBaseUrl() .'" />', $htmlBody);
			}
		}

		return new Response($this->engine->render(
			'@LeadingSystemsMerconis/backend/show_mail_html_body.html.twig',
			['mailHTMLBody' => $htmlBody]
        ));
	}
}
