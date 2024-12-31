import openai
import requests
import os

openai.api_key = os.getenv("OPENAI_API_KEY")

def ask_gpt(prompt):
    response = openai.Completion.create(
        model="gpt-3.5-turbo-0125",
        prompt=prompt,
        max_tokens=150
    )
    return response.choices[0].text.strip()

def send_message_to_socket(message):
    requests.post('http://127.0.0.1:6001/api/message', json={'message': message})

if __name__ == "__main__":
    user_input = "Digite sua mensagem aqui"
    gpt_response = ask_gpt(user_input)
    send_message_to_socket(gpt_response)
