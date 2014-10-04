{% extends "default/layout/layout_1_col.tpl" %}

{% block content %}
    <div class="row">
        <div class="span3">
            <div class="social-menu">
                <div class="social-content-image">
                    <div class="well social-background-content">
                        {{ socialPerfilPic }}
                    </div>
                </div>                
                <div class="social_menu_items">
                    {{ socialPerfilMenu }}
                </div>
                
                
                {{ socialPerfilAdditional }}
                
                <div class="well sidebar-nav">
                    {{socialPerfilFriends}}
                </div>
            </div>           
            
            
            {{ social_left_menu }}
        </div>
        <div class="span9">
            <div class="row">
                <span id="message_ajax_reponse" class="span9"></span>
                {{ social_right_content}}
                <div id="display_response_id" class="span9"></div>
            </div>
        </div>
    </div>/home/acopitan/www/chamilo19/main/template/default/layout/social_layout2.tpl
{% endblock %}