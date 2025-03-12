<?php

namespace Drupal\event_evaluation\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

class EventEvaluationForm extends FormBase
{
    private $node;

    public function __construct(Node $node)
    {
        $this->node = $node;
    }
    /**
     *  {@inheritDoc}
     */
    public function getFormId()
    {
        return 'event_evalution_form';
    }

    /**
     *  {@inheritDoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {   
        $form['areas_for_improvement'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Areas for improvement'),
            '#default_value' => $this->node?->field_areas_for_improvement->value,
            '#required' => true
        ];
        $form['actual_program_expense'] = [
            '#type' => 'number',
            '#title' => $this->t('Actual Program Expense'),
            '#default_value' => $this->node?->field_actual_program_expense->value,
            '#min' => 0,
            '#step' => 0.01,
            '#required' => true,
            '#field_prefix' => '$'
        ];
        $form['actual_program_attendance'] = [
            '#type' => 'number',
            '#title' => $this->t('Actual Program Attendance'),
            '#default_value' => $this->node?->field_actual_program_attendance->value,
            '#min' => 0,
            '#step' => 1,
            '#required' => true
        ];
        $form['actual_program_outcomes'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Actual Program Outcomes'),
            '#default_value' => $this->node?->field_actual_program_outcomes->value,
            '#required' => true
        ];
        $form['submit'] = [
            '#type' => 'submit',
            '#value' => $this->node?->field_areas_for_improvement->value ? $this->t('Update') : $this->t('Submit'),
        ];
        return $form;
    }


    /**
     * {@inheritDoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $node_id = \Drupal::routeMatch()->getParameter('node');
        $node = \Drupal\node\Entity\Node::load($node_id);
        
        $node->set('field_areas_for_improvement', $form_state->getValue('areas_for_improvement'));
        $node->set('field_actual_program_expense', $form_state->getValue('actual_program_expense'));
        $node->set('field_actual_program_attendance', $form_state->getValue('actual_program_attendance'));
        $node->set('field_actual_program_outcomes', $form_state->getValue('actual_program_outcomes'));
        $node->set('field_evaluated_by', \Drupal::currentUser()->id());
        $node->set('field_evaluated_on', time());
        $node->setRevisionLogMessage($this->t('Evaluated the event'));

        $node->save();

        \Drupal::messenger()->addMessage("Event \"{$node->getTitle()}\" has been evaluated.");
        \Drupal::service('event_evaluation.evaluation_confirmation_handler')->sendConfirmationEmail($node);
    }
}
