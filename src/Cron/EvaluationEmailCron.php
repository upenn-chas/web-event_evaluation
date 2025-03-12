<?php

namespace Drupal\event_evaluation\Cron;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\easy_email\Service\EmailHandlerInterface;
use Drupal\node\Entity\Node;

class EvaluationEmailCron
{

    protected $mailer;
    protected $logger;

    public function __construct(EmailHandlerInterface $mailer, LoggerChannelFactoryInterface $logger)
    {
        $this->mailer = $mailer;
        $this->logger = $logger->get(get_class());
    }

    public function notifyEndEventsForEvaluation()
    {
        $currentDateAndTime = \Drupal::time()->getRequestTime();

        $query = \Drupal::entityQuery('node')
            ->accessCheck(false)
            ->condition('field_event_ends_on', $currentDateAndTime, '<')
            ->condition('field_evaluation_notification', false);

        $nodeIds = $query->execute();

        if ($nodeIds) {
            $nodes = Node::loadMultiple($nodeIds);
            foreach ($nodes as $node) {
                try {
                    $this->sendEvaluationEmailLink($node);
                    $node->set('field_evaluation_notification', TRUE);
                    $node->save();
                    $this->logger->info('Sent evaluation form link: ' . $node->getTitle());
                } catch (\Exception $e) {
                    $this->logger->error($e->getMessage(), $e->getTrace());
                }
            }
        } else {
            $this->logger->debug('No events found.');
        }
    }

    protected function sendEvaluationEmailLink(Node $node)
    {
        $email = $this->mailer->createEmail([
            'type' => 'et_event_evaluation_email',
            'label' => 'Evaluation: ' . $node->getTitle()
        ]);
        if ($email) {
            $email->set('field_event', $node);
            $email->set('field_url', \Drupal\Core\Url::fromRoute('event_evaluation_operation_option', [
                'node' => $node->id(),
            ], ['absolute' => true])->toString());
            $email->setRecipientIds([$node->getOwnerId()]);
            $this->mailer->sendEmail($email, [], true, true);
        }
    }
}
