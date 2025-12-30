package com.ecommerce.cart.controller;

import java.util.UUID;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.data.redis.core.ReactiveRedisTemplate;
import org.springframework.data.redis.core.ReactiveValueOperations;
import org.springframework.web.bind.annotation.CrossOrigin;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import com.ecommerce.cart.model.Cart;
import com.ecommerce.cart.model.CartItem;

import reactor.core.publisher.Flux;
import reactor.core.publisher.Mono;

@CrossOrigin
@RestController
public class CartController {

    private static final Logger LOG = LoggerFactory.getLogger(CartController.class);

    private ReactiveRedisTemplate<String, Cart> redisTemplate;

    private ReactiveValueOperations<String, Cart> cartOps;

    CartController(ReactiveRedisTemplate<String, Cart> redisTemplate) {
        this.redisTemplate = redisTemplate;
        this.cartOps = this.redisTemplate.opsForValue();
    }

    @RequestMapping("/")
    public String index() {
        return "{ \"name\": \"Cart API\", \"version\": 1.0.0} ";
    }

    @GetMapping("/cart")
    public Flux<Cart> list() {
        return redisTemplate.keys("*")
                .flatMap(cartOps::get);
    }

    @GetMapping("/cart/{customerId}")
    public Mono<Cart> findById(@PathVariable String customerId) {
        return cartOps.get(customerId);
    }

    @PostMapping("/cart")
    public Mono<Boolean> create(@RequestBody Mono<Cart> cartMono) {
        return cartMono.flatMap(newCart -> {
            String customerId = newCart.getCustomerId();
            if (customerId == null) {
                LOG.error("Customer Id is missing.");
                return Mono.just(false);
            }

            // 1. Lấy giỏ hàng cũ từ Redis
            return cartOps.get(customerId)
                    .defaultIfEmpty(newCart) // Nếu Redis chưa có, dùng luôn object newCart để xử lý logic tính tiền lần đầu
                    .flatMap(currentCart -> {
                        // Nếu tìm thấy giỏ hàng cũ (currentCart khác newCart về tham chiếu bộ nhớ nếu logic defaultIfEmpty không được kích hoạt,
                        // tuy nhiên để an toàn ta kiểm tra logic merge)
                        if (currentCart != newCart && currentCart.getItems() != null) {

                            // 2. Logic Merge: Update số lượng hoặc thêm mới
                            if (newCart.getItems() != null) {
                                for (CartItem newItem : newCart.getItems()) {
                                    boolean isExist = false;

                                    for (CartItem existingItem : currentCart.getItems()) {
                                        // Kiểm tra trùng sản phẩm
                                        if (existingItem.getProductId().equals(newItem.getProductId())) {
                                            // Update quantity (cộng dồn số lượng cũ và mới)
                                            existingItem.setQuantity(existingItem.getQuantity() + newItem.getQuantity());
                                            isExist = true;
                                            break;
                                        }
                                    }

                                    // Nếu không trùng thì thêm vào list hiện tại
                                    if (!isExist) {
                                        currentCart.getItems().add(newItem);
                                    }
                                }
                            }
                        } else {
                            // Trường hợp Redis chưa có gì, currentCart chính là newCart
                            // Đảm bảo list items không null để tránh lỗi khi tính total
                            if(currentCart.getItems() == null) {
                                currentCart.setItems(new java.util.ArrayList<>());
                            }
                        }

                        // 3. Tính lại tổng tiền (Total)
                        float total = 0;
                        for (CartItem item : currentCart.getItems()) {
                            total += item.getPrice() * item.getQuantity();
                        }
                        currentCart.setTotal(total);

                        // 4. Lưu ngược vào Redis
                        LOG.info("Updating cart in Redis for customer: {}", customerId);
                        return cartOps.set(customerId, currentCart);
                    });
        });
    }
}