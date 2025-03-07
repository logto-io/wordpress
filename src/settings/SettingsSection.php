<?php declare(strict_types=1);
namespace Logto\WpPlugin\Settings;

class SettingsSection
{
  const allowedTextHtml = [
    'p' => ['class' => true],
    'span' => [],
    'b' => [],
    'code' => [],
    'a' => ['href' => true, 'target' => true, 'rel' => true],
  ];

  const allowedInputHtml = [
    'input' => [
      'type' => true,
      'id' => true,
      'name' => true,
      'value' => true,
      'style' => true,
      'readonly' => true,
      'placeholder' => true,
      'checked' => true,
      'class' => true,
      'disabled' => true,
    ],
    'label' => ['for' => true],
  ];

  const allowedButtonHtml = [
    'button' => [
      'id' => true,
      'type' => true,
      'class' => true,
      'style' => true,
      'onclick' => true,
      'disabled' => true,
    ],
  ];

  protected $rendered = false;
  /**
   * The script ID for the dynamic script added to the page. All scripts are shared across all
   * sections.
   */
  protected string $scriptId;

  public function __construct(
    public string $page,
    public string $optionName,
    public string $id,
    public string $title,
    public ?string $description,
  ) {
    $this->scriptId = 'logto-admin-settings' . $this->id;
  }

  public function render(): void
  {
    if ($this->rendered) {
      return;
    }

    add_settings_section(
      $this->id,
      $this->title,
      fn() => $this->description ? print wp_kses(
        "<p class='subtitle'>{$this->description}</p>",
        self::allowedTextHtml
      ) : null,
      $this->page,
    );

    $this->rendered = true;
  }

  public function addReadonlyField(
    string $id,
    string $title,
    string $description,
    ?string $value,
  ): void {
    if (!$this->rendered) {
      throw new \Exception('Settings section must be rendered before adding input fields');
    }

    add_settings_field(
      $id,
      $title,
      fn() => $this->renderReadonlyField($id, $description, $value ?? ''),
      $this->page,
      $this->id,
    );
  }

  protected function renderReadonlyField(string $id, string $description, string $value): void
  {
    printf(
      '<input type="text" id="%s" name="%s[%s]" value="%s" style="width: 300px;" readonly />',
      esc_attr($id),
      esc_attr($this->optionName),
      esc_attr($id),
      esc_attr($value)
    );

    echo wp_kses(
      sprintf('<p class="description">%s</p>', wp_kses($description, self::allowedTextHtml)),
      array_merge(self::allowedTextHtml, ['p' => ['class' => true]])
    );
  }

  public function addInputField(
    string $id,
    string $title,
    string $description,
    ?string $value,
    string $type = 'text',
  ): void {
    if (!$this->rendered) {
      throw new \Exception('Settings section must be rendered before adding input fields');
    }

    add_settings_field(
      $id,
      $title,
      fn() => $this->renderInputField($id, $type, $description, $value ?? ''),
      $this->page,
      $this->id,
    );
  }

  protected function renderInputField(string $id, string $type, string $description, string $value): void
  {
    printf(
      '<input type="%s" id="%s" name="%s[%s]" value="%s" style="width: 300px;" />',
      esc_attr($type),
      esc_attr($id),
      esc_attr($this->optionName),
      esc_attr($id),
      esc_attr($value)
    );
    echo wp_kses(
      sprintf('<p class="description">%s</p>', wp_kses($description, self::allowedTextHtml)),
      array_merge(self::allowedTextHtml, ['p' => ['class' => true]])
    );
  }

  public function addSwitchField(
    string $id,
    string $title,
    ?string $label,
    string $description,
    bool $value,
  ): void {
    if (!$this->rendered) {
      throw new \Exception('Settings section must be rendered before adding input fields');
    }

    add_settings_field(
      $id,
      $title,
      fn() => $this->renderSwitchField($id, $label, $description, $value),
      $this->page,
      $this->id,
    );
  }

  protected function renderSwitchField(string $id, ?string $label, string $description, bool $value): void
  {
    printf(
      '<input type="checkbox" id="%1$s" name="%2$s[%1$s]" %3$s />',
      esc_attr($id),
      esc_attr($this->optionName),
      checked($value, true, false)
    );

    if ($label) {
      echo wp_kses(
        sprintf('<label for="%s">%s</label>', esc_attr($id), esc_html($label)),
        ['label' => ['for' => true]]
      );
    }

    echo wp_kses(
      sprintf('<p class="description">%s</p>', wp_kses($description, self::allowedTextHtml)),
      array_merge(self::allowedTextHtml, ['p' => ['class' => true]])
    );
  }

  public function addRadioField(
    string $id,
    string $title,
    string $description,
    string $value,
    array $options,
  ): void {
    if (!$this->rendered) {
      throw new \Exception('Settings section must be rendered before adding input fields');
    }

    add_settings_field(
      $id,
      $title,
      fn() => $this->renderRadioField($id, $description, $value, $options),
      $this->page,
      $this->id,
    );
  }

  protected function renderRadioField(string $id, string $description, string $value, array $options): void
  {
    echo wp_kses(
      "<div class='radio-group'>",
      ['div' => ['class' => true]]
    );
    foreach ($options as $optionValue => $optionLabel) {
      echo wp_kses(
        "<div>",
        ['div' => []]
      );
      printf(
        '<input type="radio" id="%1$s-%2$s" name="%3$s[%1$s]" value="%2$s" %4$s />',
        esc_attr($id),
        esc_attr($optionValue),
        esc_attr($this->optionName),
        checked($value, $optionValue, false)
      );
      echo wp_kses(
        sprintf(
          '<label for="%1$s-%2$s">%3$s</label>',
          esc_attr($id),
          esc_attr($optionValue),
          wp_kses($optionLabel, self::allowedTextHtml)
        ),
        array_merge(self::allowedTextHtml, ['label' => ['for' => true]])
      );

      echo wp_kses("</div>", ['div' => []]);
    }
    echo wp_kses("</div>", ['div' => []]);
    echo wp_kses(
      sprintf('<p class="description">%s</p>', wp_kses($description, self::allowedTextHtml)),
      array_merge(self::allowedTextHtml, ['p' => ['class' => true]])
    );
  }

  public function addKeyValuePairsField(
    string $id,
    string $title,
    string $description,
    array $value,
    array $options = [],
  ): void {
    if (!$this->rendered) {
      throw new \Exception('Settings section must be rendered before adding input fields');
    }

    add_settings_field(
      $id,
      $title,
      fn() => $this->renderKeyValuePairsField($id, $description, $value, $options),
      $this->page,
      $this->id,
    );
  }

  protected function renderKeyValuePairsField(string $id, string $description, array $value, array $options): void
  {
    echo wp_kses(
      sprintf(
        '<div id="edit-%s" style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 8px;">',
        esc_attr($id)
      ),
      ['div' => ['id' => true, 'style' => true]]
    );

    foreach ($value as [$key, $val]) {
      echo wp_kses(
        '<div style="display: flex; gap: 8px; align-items: center;">',
        ['div' => ['style' => true]]
      );

      echo wp_kses(
        $this->getKeyValuePairString($id, esc_attr($key), esc_attr($val), $options),
        array_merge(self::allowedInputHtml, self::allowedButtonHtml)
      );
      echo wp_kses('</div>', ['div' => []]);
    }

    echo wp_kses('</div>', ['div' => []]);
    echo wp_kses(
      sprintf(
        '<button id="add-%s" type="button" class="button">%s</button>',
        esc_attr($id),
        esc_html(__('Add', 'logto'))
      ),
      ['button' => ['id' => true, 'type' => true, 'class' => true]]
    );
    echo wp_kses(
      sprintf('<p class="description">%s</p>', wp_kses($description, self::allowedTextHtml)),
      array_merge(self::allowedTextHtml, ['p' => ['class' => true]])
    );

    if (!wp_script_is($this->scriptId, 'registered')) {
      wp_register_script($this->scriptId, '');
      wp_enqueue_script($this->scriptId);
    }

    wp_add_inline_script($this->scriptId, sprintf('
      document.addEventListener("DOMContentLoaded", function () {
        const addBtn = document.getElementById("add-%s");
        const editDiv = document.getElementById("edit-%s");

        addBtn.addEventListener("click", function () {
          const div = document.createElement("div");
          div.innerHTML = `%s`;
          editDiv.appendChild(div);
        });

        editDiv.addEventListener("click", function (e) {
          if (e.target.tagName === "BUTTON") {
            e.target.parentElement.remove();
          }
        });
      });
    ',
      esc_js($id),
      esc_js($id),
      wp_kses($this->getKeyValuePairString($id, '', '', $options), array_merge(self::allowedInputHtml, self::allowedButtonHtml))
    ));
  }

  protected function getKeyValuePairString(string $id, string $key, string $val, array $options): string
  {
    return
      sprintf(
        '<input type="text" name="%1$s[%2$s][keys][]" value="%3$s" style="width: 150px;" placeholder="%4$s" />',
        esc_attr($this->optionName),
        esc_attr($id),
        esc_attr($key),
        esc_attr($options['keyPlaceholder'] ?? 'Key')
      ) .
      sprintf(
        '<input type="text" name="%1$s[%2$s][values][]" value="%3$s" style="width: 150px;" placeholder="%4$s" />',
        esc_attr($this->optionName),
        esc_attr($id),
        esc_attr($val),
        esc_attr($options['valuePlaceholder'] ?? 'Value')
      ) .
      sprintf('<button type="button" class="button">%s</button>', esc_html(__('Remove', 'logto')));
  }
}
